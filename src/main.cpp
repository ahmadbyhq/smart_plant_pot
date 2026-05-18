#include <Arduino.h>
#include <Wire.h>
#include <DHT.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
// #include <FluxGarage_RoboEyes.h>

#define OLED_SDA 21
#define OLED_SCL 22
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64

#define SOIL_MOISTURE_PIN 34
#define WATER_LEVEL_PIN 35
#define RELAY_PIN 26
#define BUZZER_PIN 27
#define BTN_RESET_WIFI 0

#define DHTPIN 4
#define DHTTYPE DHT22

#define TEMP_THRESHOLD  0.5f
#define HUM_THRESHOLD   2.0f
#define SOIL_THRESHOLD  3
#define WATER_THRESHOLD 3 

// #define FB_PATH_TEMP    "/smart-pot/temperature"
// #define FB_PATH_HUM     "/smart-pot/humidity"
// #define FB_PATH_SOIL    "/smart-pot/soilPercent"
// #define FB_PATH_WATER   "/smart-pot/waterPercent"


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

Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);
DHT dht(DHTPIN, DHTTYPE);
// RoboEyes<Adafruit_SSD1306> roboEyes(display); 

SensorReading  current;
SensorSnapshot previous;

unsigned long previousSensorMillis = 0;
const unsigned long sensorInterval = 2000;

void readSensors();
bool isValidReading();
ChangedFields detectChanges();
void applySnapshot(const ChangedFields& changed);
void printSerialMonitor(const ChangedFields& changed);
void displayMonitoring(const ChangedFields& changed);
// void uploadFirebase(const ChangedFields& changed);

void setup() {
    Serial.begin(115200);

    pinMode(RELAY_PIN, OUTPUT);
    pinMode(BUZZER_PIN, OUTPUT);
    pinMode(BTN_RESET_WIFI, INPUT_PULLUP);

    digitalWrite(RELAY_PIN, HIGH); //low level trigger
    digitalWrite(BUZZER_PIN, LOW);

    analogReadResolution(12);
    analogSetAttenuation(ADC_11db);

    dht.begin();

    Wire.begin(OLED_SDA, OLED_SCL);

    if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
        Serial.println(F("[ERROR] OLED tidak terdeteksi!"));
        for (;;);
    }

    display.clearDisplay();
    display.setTextSize(1);
    display.setTextColor(SSD1306_WHITE);
    display.setCursor(0, 20);
    display.println(" Smart Plant Pot");
    display.setCursor(0, 36);
    display.println("  Initializing...");
    display.display();

    Serial.println("Setup selesai");

}

void loop() {
    unsigned long currentMillis = millis();

    if (currentMillis - previousSensorMillis >= sensorInterval) {
        previousSensorMillis = currentMillis;

        readSensors();

        // Validasi
        if (!isValidReading()) {
            Serial.println("[WARN] Pembacaan sensor tidak valid, dilewati");
            return;
        }

        // Deteksi field mana saja yang berubah signifikan
        ChangedFields changed = detectChanges();

        if (changed.any()) {
            printSerialMonitor(changed);
            displayMonitoring(changed);
            // uploadFirebase(changed);
            applySnapshot(changed);
        }
    }
}

void readSensors() {
    current.temperature = dht.readTemperature();
    current.humidity    = dht.readHumidity();

    long soilSum = 0, waterSum = 0;
    for (int i = 0; i < 3; i++) {
        soilSum  += analogRead(SOIL_MOISTURE_PIN);
        waterSum += analogRead(WATER_LEVEL_PIN);
        delay(5);
    }
    current.soilRaw  = soilSum  / 3;
    current.waterRaw = waterSum / 3;

    current.soilPercent = constrain(
        map(current.soilRaw, 3200, 1200, 0, 100), 0, 100
    );
    
    current.waterPercent = constrain(
        map(current.waterRaw, 0, 4095, 0, 100), 0, 100
    );
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

void printSerialMonitor(const ChangedFields& changed) {
    Serial.println("===== SMART PLANT MONITOR =====");
    if (changed.temperature) {
        Serial.print("Temperature : ");
        Serial.print(current.temperature, 1);
        Serial.println(" C  [UPDATE]");
    }
    if (changed.humidity) {
        Serial.print("Humidity    : ");
        Serial.print(current.humidity, 1);
        Serial.println(" %  [UPDATE]");
    }
    if (changed.soilPercent) {
        Serial.print("Soil        : ");
        Serial.print(current.soilPercent);
        Serial.println(" %  [UPDATE]");
    }
    if (changed.waterPercent) {
        Serial.print("Water Level : ");
        Serial.print(current.waterPercent);
        Serial.println(" %  [UPDATE]");
    }
    Serial.println("===============================\n");
}

void displayMonitoring(const ChangedFields& changed) {
    float dispTemp  = changed.temperature  ? current.temperature  : previous.temperature;
    float dispHum   = changed.humidity     ? current.humidity     : previous.humidity;
    int   dispSoil  = changed.soilPercent  ? current.soilPercent  : previous.soilPercent;
    int   dispWater = changed.waterPercent ? current.waterPercent : previous.waterPercent;

    display.clearDisplay();
    display.setTextSize(1);
    display.setTextColor(SSD1306_WHITE);

    display.setCursor(0, 0);
    display.print("Temp : ");
    display.print(dispTemp, 1);
    display.print(" C");
    if (changed.temperature) display.print(" *");

    display.setCursor(0, 16);
    display.print("Hum  : ");
    display.print(dispHum, 1);
    display.print(" %");
    if (changed.humidity) display.print(" *");

    display.setCursor(0, 32);
    display.print("Soil : ");
    display.print(dispSoil);
    display.print(" %");
    if (changed.soilPercent) display.print(" *");

    display.setCursor(0, 48);
    display.print("Water: ");
    display.print(dispWater);
    display.print(" %");
    if (changed.waterPercent) display.print(" *");

    display.display();
}

// void uploadFirebase(const ChangedFields& changed) {

//     /*
//     if (changed.temperature) {
//         if (!Firebase.setFloat(fbData, FB_PATH_TEMP, current.temperature))
//             Serial.printf("[FB ERR] temp: %s\n", fbData.errorReason().c_str());
//     }
//     if (changed.humidity) {
//         if (!Firebase.setFloat(fbData, FB_PATH_HUM, current.humidity))
//             Serial.printf("[FB ERR] hum: %s\n", fbData.errorReason().c_str());
//     }
//     if (changed.soilPercent) {
//         if (!Firebase.setInt(fbData, FB_PATH_SOIL, current.soilPercent))
//             Serial.printf("[FB ERR] soil: %s\n", fbData.errorReason().c_str());
//     }
//     if (changed.waterPercent) {
//         if (!Firebase.setInt(fbData, FB_PATH_WATER, current.waterPercent))
//             Serial.printf("[FB ERR] water: %s\n", fbData.errorReason().c_str());
//     }
//     */

    // Serial.print("[Firebase] Would update:");
    // if (changed.temperature)  Serial.print(" temperature");
    // if (changed.humidity)     Serial.print(" humidity");
    // if (changed.soilPercent)  Serial.print(" soilPercent");
    // if (changed.waterPercent) Serial.print(" waterPercent");
    // Serial.println();
// }