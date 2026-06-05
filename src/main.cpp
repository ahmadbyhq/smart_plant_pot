#include <Arduino.h>
#include <Wire.h>
#include <DHT.h>
// #include <Adafruit_GFX.h>
// #include <Adafruit_SSD1306.h>
#include <U8g2lib.h>
#include <WiFi.h>
#include <HTTPClient.h>

// #include <FluxGarage_RoboEyes.h>

#define OLED_SDA 21
#define OLED_SCL 22
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64

#define SOIL_MOISTURE_PIN 34
#define WATER_LEVEL_PIN 35
#define RELAY_PIN 26
#define BUZZER_PIN 27
// #define BTN_RESET_WIFI 18

#define DHTPIN 4
#define DHTTYPE DHT22

#define TEMP_THRESHOLD  0.5f
#define HUM_THRESHOLD   2.0f
#define SOIL_THRESHOLD  3
#define WATER_THRESHOLD 3 

struct SensorReading {
    float temperature  = NAN;
    float humidity     = NAN;
    int   soilRaw      = 0;
    int   waterRaw     = 0;
    int   soilPercent  = 0;
    int   waterPercent = 0;
};

struct SensorSnapshot {
    float temperature  = NAN;
    float humidity     = NAN;
    int   soilPercent  = -1;  // -1 = belum pernah ditampilkan
    int   waterPercent = -1;
};

struct ChangedFields {
    bool temperature  = false;
    bool humidity     = false;
    bool soilPercent  = false;
    bool waterPercent = false;

    // true jika minimal satu field berubah
    bool any() const {
        return temperature || humidity || soilPercent || waterPercent;
    }
};

struct MonitoringData
{
    float temperature;
    float humidity;

    int soilRaw;
    int soilPercent;

    int waterRaw;
    int waterPercent;

    bool pumpStatus;

    unsigned long timestamp;
};

struct AlarmThreshold
{
    // Soil
    int soilWarningPercent = 30;
    int soilCriticalPercent = 20;

    // Water Tank
    int waterWarningPercent = 20;
    int waterCriticalPercent = 10;

    // Temperature
    float tempHigh = 35.0;
    float tempLow = 15.0;

    // Humidity
    float humLow = 30.0;
};

AlarmThreshold threshold;

enum class AlertState
{
    NORMAL,
    WARNING,
    CRITICAL
};

AlertState soilAlert = AlertState::NORMAL;
AlertState waterAlert = AlertState::NORMAL;
AlertState tempAlert = AlertState::NORMAL;

// Soil Moisture Calibration
const int SOIL_DRY_ADC = 2490;
const int SOIL_WET_ADC = 870;

// Water Level Calibration
const int WATER_EMPTY_ADC = 1000;
const int WATER_FULL_ADC  = 1400;

// SSID dan Password
const char* WIFI_SSID = "SAPU";
const char* WIFI_PASS = "korek111";

// Variable Pengatur Buzzer
bool buzzerActive = false;
bool buzzerState = false;

unsigned long buzzerStartMillis = 0;
unsigned long buzzerPreviousMillis = 0;

const unsigned long BUZZER_DURATION = 5000; // 5 detik
const unsigned long BUZZER_INTERVAL = 1000;  // 1 detik

AlertState previousSoilAlert = AlertState::NORMAL;
AlertState previousWaterAlert = AlertState::NORMAL;

const unsigned long heartbeatInterval = 300000UL;
unsigned long previousHeartbeatMillis = 0;

U8G2_SSD1306_128X64_NONAME_F_HW_I2C u8g2(U8G2_R0, /* reset=*/ U8X8_PIN_NONE, /* clock=*/ OLED_SCL, /* data=*/ OLED_SDA);
DHT dht(DHTPIN, DHTTYPE);

SensorReading  current;
SensorSnapshot previous;

unsigned long previousSensorMillis = 0;
const unsigned long sensorInterval = 2000;

unsigned long buttonPressStart = 0;
bool buttonHeld = false;

void readSensors();
bool isValidReading();
ChangedFields detectChanges();
void applySnapshot(const ChangedFields& changed);
void displayMonitoring(const ChangedFields& changed);
void evaluateAlerts();
void controlPump();
void controlBuzzer();
void printAlertState(AlertState state);
void printSerialMonitor();
String alertStateToString(AlertState state);
void uploadMonitoring();

void setup() {
    Serial.begin(115200);

    pinMode(RELAY_PIN, OUTPUT);
    pinMode(BUZZER_PIN, OUTPUT);

    digitalWrite(RELAY_PIN, HIGH);
    digitalWrite(BUZZER_PIN, LOW);

    analogReadResolution(12);
    analogSetAttenuation(ADC_11db);

    dht.begin();

    Wire.begin(OLED_SDA, OLED_SCL);

    u8g2.begin();
    u8g2.clearBuffer();					
    u8g2.setFont(u8g2_font_ncenB08_tr); 
    u8g2.drawStr(0, 20, "Smart Plant Pot");
    u8g2.drawStr(0, 36, "Initializing...");
    u8g2.sendBuffer();

    WiFi.begin(WIFI_SSID, WIFI_PASS);

    Serial.print("Connecting WiFi");

    unsigned long wifiStartTime = millis();
    const unsigned long WIFI_TIMEOUT = 30000; // 30 detik

    while(
        WiFi.status() != WL_CONNECTED &&
        millis() - wifiStartTime < WIFI_TIMEOUT
    )
    {
        delay(500);
        Serial.print(".");
    }


    if(WiFi.status() == WL_CONNECTED)
    {
        Serial.println();
        Serial.println("WiFi Connected");
        Serial.println(WiFi.localIP());
    }
    else
    {
        Serial.println();
        Serial.println("[ERROR] WiFi Connection Timeout");
        u8g2.clearBuffer();
        u8g2.drawStr(0, 20, "WiFi Failed");
        u8g2.drawStr(0, 36, "Offline Mode");
        u8g2.sendBuffer();
    }

    Serial.println("Setup selesai");

}

void loop() {
    unsigned long currentMillis = millis();

    controlBuzzer();

    if (currentMillis - previousSensorMillis >= sensorInterval) {
        previousSensorMillis = currentMillis;

        readSensors();

        // Validasi
        if (!isValidReading()) {
            Serial.println("[WARN] Pembacaan sensor tidak valid, dilewati");
            return;
        }

                
        evaluateAlerts();

        if(
            soilAlert == AlertState::CRITICAL &&
            previousSoilAlert != AlertState::CRITICAL
        )
        {
            buzzerActive = true;

            buzzerStartMillis = millis();
            buzzerPreviousMillis = millis();

            buzzerState = true;

            digitalWrite(BUZZER_PIN, HIGH);
        }

        if(
            waterAlert == AlertState::CRITICAL &&
            previousWaterAlert != AlertState::CRITICAL
        )
        {
            buzzerActive = true;

            buzzerStartMillis = millis();
            buzzerPreviousMillis = millis();

            buzzerState = true;

            digitalWrite(BUZZER_PIN, HIGH);
        }

        previousSoilAlert = soilAlert;
        previousWaterAlert = waterAlert;

        controlPump();

        // Deteksi field mana saja yang berubah signifikan
        ChangedFields changed = detectChanges();

        if (changed.any())
        {
            uploadMonitoring();

            applySnapshot(changed);

            printSerialMonitor();

            displayMonitoring(changed);
        }
    }


    if(currentMillis - previousHeartbeatMillis >= heartbeatInterval)
    {
        previousHeartbeatMillis = currentMillis;

        uploadMonitoring();

        Serial.println("\n[HEARTBEAT] System still running");

        printSerialMonitor();
    }
}

void readSensors() {
    current.temperature = dht.readTemperature();
    current.humidity    = dht.readHumidity();

    long soilSum = 0, waterSum = 0;
    for (int i = 0; i < 10; i++) {
        soilSum  += analogRead(SOIL_MOISTURE_PIN);
        waterSum += analogRead(WATER_LEVEL_PIN);
        delay(5);
    }
    current.soilRaw  = soilSum  / 10;
    current.waterRaw = waterSum / 10;

    current.soilPercent = constrain(
        map(current.soilRaw, SOIL_DRY_ADC, SOIL_WET_ADC, 0, 100), 0, 100
    );
    
    current.waterPercent = constrain(
        map(current.waterRaw, WATER_EMPTY_ADC, WATER_FULL_ADC, 0, 100), 0, 100
    );

    // Serial.println("===== RAW ADC =====");

    // Serial.print("Soil Raw  : ");
    // Serial.println(current.soilRaw);

    // Serial.print("Water Raw : ");
    // Serial.println(current.waterRaw);

    // Serial.println("===================");
}

bool isValidReading() {
    return !isnan(current.temperature) && !isnan(current.humidity);
}

ChangedFields detectChanges() {
    ChangedFields changed;

    changed.temperature = isnan(previous.temperature) ||
        (fabsf(current.temperature - previous.temperature) >= TEMP_THRESHOLD);

    changed.humidity = isnan(previous.humidity) ||
        (fabsf(current.humidity - previous.humidity) >= HUM_THRESHOLD);

    changed.soilPercent = (previous.soilPercent < 0) ||
        (abs(current.soilPercent - previous.soilPercent) >= SOIL_THRESHOLD);

    changed.waterPercent = (previous.waterPercent < 0) ||
        (abs(current.waterPercent - previous.waterPercent) >= WATER_THRESHOLD);

    return changed;
}

void applySnapshot(const ChangedFields& changed) {
    if (changed.temperature)  previous.temperature  = current.temperature;
    if (changed.humidity)     previous.humidity     = current.humidity;
    if (changed.soilPercent)  previous.soilPercent  = current.soilPercent;
    if (changed.waterPercent) previous.waterPercent = current.waterPercent;
}

void displayMonitoring(const ChangedFields& changed) {
    float dispTemp  = changed.temperature  ? current.temperature  : previous.temperature;
    float dispHum   = changed.humidity     ? current.humidity     : previous.humidity;
    int   dispSoil  = changed.soilPercent  ? current.soilPercent  : previous.soilPercent;
    int   dispWater = changed.waterPercent ? current.waterPercent : previous.waterPercent;

    u8g2.clearBuffer();
    u8g2.setFont(u8g2_font_ncenB08_tr);

    char buffer[32];
    
    sprintf(buffer, "Temp : %.1f C %s", dispTemp, changed.temperature ? "*" : "");
    u8g2.drawStr(0, 10, buffer);

    sprintf(buffer, "Hum  : %.1f %% %s", dispHum, changed.humidity ? "*" : "");
    u8g2.drawStr(0, 26, buffer);

    sprintf(buffer, "Soil : %d %% %s", dispSoil, changed.soilPercent ? "*" : "");
    u8g2.drawStr(0, 42, buffer);

    sprintf(buffer, "Water: %d %% %s", dispWater, changed.waterPercent ? "*" : "");
    u8g2.drawStr(0, 58, buffer);

    u8g2.sendBuffer();
}

void evaluateAlerts()
{
    // Soil
    if(current.soilPercent <= threshold.soilCriticalPercent)
    {
        soilAlert = AlertState::CRITICAL;
    }
    else if(current.soilPercent <= threshold.soilWarningPercent)
    {
        soilAlert = AlertState::WARNING;
    }
    else
    {
        soilAlert = AlertState::NORMAL;
    }

    // Water Tank
    if(current.waterPercent <= threshold.waterCriticalPercent)
    {
        waterAlert = AlertState::CRITICAL;
    }
    else if(current.waterPercent <= threshold.waterWarningPercent)
    {
        waterAlert = AlertState::WARNING;
    }
    else
    {
        waterAlert = AlertState::NORMAL;
    }

    // Temperature
    if(current.temperature >= threshold.tempHigh)
    {
        tempAlert = AlertState::WARNING;
    }
    else
    {
        tempAlert = AlertState::NORMAL;
    }
}

void controlPump()
{
    if(
        soilAlert == AlertState::CRITICAL &&
        waterAlert != AlertState::CRITICAL
    )
    {
        digitalWrite(RELAY_PIN, LOW); // ON
    }
    else
    {
        digitalWrite(RELAY_PIN, HIGH); // OFF
    }
}

void controlBuzzer()
{
    if(!buzzerActive)
    {
        digitalWrite(BUZZER_PIN, LOW);
        return;
    }

    if(millis() - buzzerStartMillis >= BUZZER_DURATION)
    {
        buzzerActive = false;
        digitalWrite(BUZZER_PIN, LOW);
        return;
    }

    if(millis() - buzzerPreviousMillis >= BUZZER_INTERVAL)
    {
        buzzerPreviousMillis = millis();

        buzzerState = !buzzerState;

        digitalWrite(
            BUZZER_PIN,
            buzzerState
        );
    }
}

void printSerialMonitor()
{
    Serial.println("\n======================================");
    Serial.println("      SMART PLANT POT MONITOR");
    Serial.println("======================================");

    Serial.printf("Temperature : %.1f C\n", current.temperature);
    Serial.printf("Humidity    : %.1f %%\n", current.humidity);

    Serial.printf(
        "Soil        : %d %% (ADC: %d)\n",
        current.soilPercent,
        current.soilRaw
    );

    Serial.printf(
        "Water Tank  : %d %% (ADC: %d)\n",
        current.waterPercent,
        current.waterRaw
    );

    Serial.println("--------------------------------------");

    Serial.print("Soil Alert  : ");
    printAlertState(soilAlert);

    Serial.print("Water Alert : ");
    printAlertState(waterAlert);

    Serial.print("Temp Alert  : ");
    printAlertState(tempAlert);

    Serial.println("--------------------------------------");

    Serial.printf(
        "Pump Status : %s\n",
        digitalRead(RELAY_PIN) == LOW ? "ON" : "OFF"
    );

    Serial.printf(
        "Buzzer      : %s\n",
        buzzerActive ? "ACTIVE" : "OFF"
    );

    Serial.printf(
        "WiFi        : %s\n",
        WiFi.status() == WL_CONNECTED ?
        "CONNECTED" : "DISCONNECTED"
    );

    if(WiFi.status() == WL_CONNECTED)
    {
        Serial.printf(
            "IP Address  : %s\n",
            WiFi.localIP().toString().c_str()
        );
    }

    Serial.println("======================================\n");
    Serial.printf("Uptime      : %lu sec\n", millis() / 1000);
}

void printAlertState(AlertState state)
{
    switch(state)
    {
        case AlertState::NORMAL:
            Serial.println("NORMAL");
            break;

        case AlertState::WARNING:
            Serial.println("WARNING");
            break;

        case AlertState::CRITICAL:
            Serial.println("CRITICAL");
            break;
    }
}

String alertStateToString(AlertState state)
{
    switch(state)
    {
        case AlertState::NORMAL:
            return "NORMAL";

        case AlertState::WARNING:
            return "WARNING";

        case AlertState::CRITICAL:
            return "CRITICAL";
    }

    return "UNKNOWN";
}

void uploadMonitoring()
{
    if(WiFi.status() != WL_CONNECTED)
    {
        Serial.println("[HTTP] WiFi disconnected");
        return;
    }

    HTTPClient http;

    http.begin(
        "http://IP_SERVER/smart-plant-pot/insert.php"
    );

    http.addHeader(
        "Content-Type",
        "application/json"
    );

    String payload = "{";

    payload += "\"temperature\":";
    payload += String(current.temperature, 1);

    payload += ",\"humidity\":";
    payload += String(current.humidity, 1);

    payload += ",\"soil_raw\":";
    payload += String(current.soilRaw);

    payload += ",\"soil_percent\":";
    payload += String(current.soilPercent);

    payload += ",\"water_raw\":";
    payload += String(current.waterRaw);

    payload += ",\"water_percent\":";
    payload += String(current.waterPercent);

    payload += ",\"pump_status\":";
    payload += (digitalRead(RELAY_PIN) == LOW ? "1" : "0");

    payload += ",\"soil_alert\":\"";
    payload += alertStateToString(soilAlert);
    payload += "\"";

    payload += ",\"water_alert\":\"";
    payload += alertStateToString(waterAlert);
    payload += "\"";

    payload += ",\"temp_alert\":\"";
    payload += alertStateToString(tempAlert);
    payload += "\"";

    payload += "}";

    Serial.println("[HTTP] Payload:");
    Serial.println(payload);

    String response = "";

    int httpCode = http.POST(payload);

    if(httpCode > 0)
    {
        response = http.getString();

        Serial.printf(
            "[HTTP] Response Code: %d\n",
            httpCode
        );

        Serial.println("[HTTP] Response:");
        Serial.println(response);
    }
    else
    {
        Serial.printf(
            "[HTTP] Request Failed: %s\n",
            http.errorToString(httpCode).c_str()
        );
    }

    http.end();
}