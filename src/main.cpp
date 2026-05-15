#include <Arduino.h>
#include <Wire.h>
#include <DHT.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <FluxGarage_RoboEyes.h>

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


Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);
DHT dht(DHTPIN, DHTTYPE);
RoboEyes<Adafruit_SSD1306> roboEyes(display); 

float currentTemperature = 0.0;
float currentHumidity = 0.0;
int currentSoilMoistureValue = 0;
int currentWaterLevelValue = 0;

// EVENT TIMER RoboEyes Animation
unsigned long eventTimer; // will save the timestamps
bool event1wasPlayed = 0; // flag variables
bool event2wasPlayed = 0;
bool event3wasPlayed = 0;

void setup() {
    Serial.begin(115200);

    pinMode(RELAY_PIN, OUTPUT);
    pinMode(BUZZER_PIN, OUTPUT);
    pinMode(BTN_RESET_WIFI, INPUT_PULLUP);

    digitalWrite(RELAY_PIN, HIGH); //low level trigger
    digitalWrite(BUZZER_PIN, LOW);

    dht.begin();

    Wire.begin(OLED_SDA, OLED_SCL);

    if(!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
        Serial.println(F("SSD1306 allocation failed"));
        for(;;);
    }

    roboEyes.begin(SCREEN_WIDTH, SCREEN_HEIGHT, 100); // screen-width, screen-height, max framerate - 60-100fps are good for smooth animations
    roboEyes.setPosition(DEFAULT); // eye position should be middle center
    roboEyes.close();

    eventTimer = millis();
}

void loop() {

    // EXAMPLE EYES ROBOT ANIMATION
    roboEyes.update(); // update eyes drawings

    // LOOPED ANIMATION SEQUENCE
    // Do once after defined number of milliseconds
    if(millis() >= eventTimer+2000 && event1wasPlayed == 0){
        event1wasPlayed = 1; // flag variable to make sure the event will only be handled once
        roboEyes.open(); // open eyes 
    }
    // Do once after defined number of milliseconds
    if(millis() >= eventTimer+4000 && event2wasPlayed == 0){
        event2wasPlayed = 1; // flag variable to make sure the event will only be handled once
        roboEyes.setMood(HAPPY);
        roboEyes.anim_laugh();
        //roboEyes.anim_confused();
    }
    // Do once after defined number of milliseconds
    if(millis() >= eventTimer+6000 && event3wasPlayed == 0){
        event3wasPlayed = 1; // flag variable to make sure the event will only be handled once
        roboEyes.setMood(TIRED);
        //roboEyes.blink();
    }
    // Do once after defined number of milliseconds, then reset timer and flags to restart the whole animation sequence
    if(millis() >= eventTimer+8000){
        roboEyes.close(); // close eyes again
        roboEyes.setMood(DEFAULT);
        // Reset the timer and the event flags to restart the whole "complex animation loop"
        eventTimer = millis(); // reset timer
        event1wasPlayed = 0; // reset flags
        event2wasPlayed = 0;
        event3wasPlayed = 0;
    }
}