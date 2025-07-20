#include <ESP8266WiFi.h>
#include <MQTT.h>

#include <DHT.h>

// DHT22
#define DHTPIN D4
#define DHTTYPE DHT22
DHT dht(DHTPIN, DHTTYPE);

// PIR
#define PIR_PIN D6
String statusGerakan = "off";

//Include the library
#include <MQUnifiedsensor.h>
/************************Hardware Related Macros************************************/
#define Board ("ESP8266")
#define Pin (A0)  //Analog input 3 of your arduino
/***********************Software Related Macros************************************/
#define Type ("MQ-3")             //MQ3
#define Voltage_Resolution (3.3)  // 3V3 <- IMPORTANT
#define ADC_Bit_Resolution (10)   // For ESP8266
#define RatioMQ3CleanAir (3.6)
/*****************************Globals***********************************************/
MQUnifiedsensor MQ3(Board, Voltage_Resolution, ADC_Bit_Resolution, Pin, Type);
/*****************************Globals***********************************************/

// Konfigurasi WiFi
const char ssid[] = "realme C53";
const char pass[] = "sabangsubik23";

// Konfigurasi MQTT

//const char mqttServer[] = "broker.emqx.io";
//const char mqttServer[] = "test.mosquitto.org";
const char mqttServer[] = "broker.hivemq.com";

const int mqttPort = 1883;
String topicSuhu = "suhukandang";
String topicKelembaban = "kelembabankandang";
String topicAmonia = "amoniakandang";
String topicPergerakan = "pergerakankandang";

// Membuat Object
WiFiClient net;
MQTTClient client;

// Variabel Status dan Waktu
unsigned long lastMillis = 0;

void setup() {
  Serial.begin(9600);
  dht.begin();

  pinMode(PIR_PIN, INPUT);

  //Set math model to calculate the PPM concentration and the value of constants
  MQ3.setRegressionMethod(1);  //_PPM =  a*ratio^b
  MQ3.setA(102.2);
  MQ3.setB(-2.473);  // Configure the equation to to calculate Benzene concentration

  /*
    Exponential regression:
    Gas    | a      | b
    LPG    | 44771  | -3.245
    CH4    | 2*10^31| 19.01
    CO     | 521853 | -3.821
    Alcohol| 0.3934 | -1.504
    Benzene| 4.8387 | -2.68
    Hexane | 7585.3 | -2.849
  */

  /*****************************  MQ Init ********************************************/
  //Remarks: Configure the pin of arduino as input.
  /************************************************************************************/
  MQ3.init();

  Serial.print("Calibrating please wait.");
  float calcR0 = 0;
  for (int i = 1; i <= 10; i++) {
    MQ3.update();  // Update data, the arduino will read the voltage from the analog pin
    calcR0 += MQ3.calibrate(RatioMQ3CleanAir);
    Serial.print(".");
  }
  MQ3.setR0(calcR0 / 10);
  Serial.println("  done!.");

  if (isinf(calcR0)) {
    Serial.println("Warning: Conection issue, R0 is infinite (Open circuit detected) please check your wiring and supply");
    while (1)
      ;
  }
  if (calcR0 == 0) {
    Serial.println("Warning: Conection issue found, R0 is zero (Analog pin shorts to ground) please check your wiring and supply");
    while (1)
      ;
  }
  /*****************************  MQ CAlibration ********************************************/
  MQ3.serialDebug(true);

}

void loop() {
  client.loop();
  if (!client.connected()) {
    connectWiFi();
    connectMQTT();
  }

  if (millis() - lastMillis > 1000) {

    lastMillis = millis();

    float suhu = dht.readTemperature();
    float kelembaban = dht.readHumidity();

    if (isnan(suhu) || isnan(kelembaban)) {
      Serial.println("Gagal membaca DHT22");
    } else {
      Serial.print("Suhu: ");
      Serial.print(suhu);
      Serial.print(" °C | ");
      Serial.print("Kelembaban: ");
      Serial.print(kelembaban);
      Serial.println(" %");
    }

    MQ3.update();                  // Update data, the arduino will read the voltage from the analog pin
    float ppm = MQ3.readSensor();  // Sensor will read PPM concentration using the model, a and b values set previously or from the setup

    Serial.print("PPM: ");
    Serial.println(ppm);  // Cetak nilai PPM ke Serial Monitor

    // PIR
    int gerakan = digitalRead(PIR_PIN);

    Serial.print("Gerakan: ");
    Serial.println(gerakan == HIGH ? "Terdeteksi!" : "Tidak ada");
    Serial.println("-------------------------------");

    if (gerakan == HIGH) {
      statusGerakan = "on";
    } else {
      statusGerakan = "off";
    }

    publishSensorData(suhu, kelembaban, ppm, statusGerakan);
    
    delay(1000);

  }

}

void connectWiFi() {
  Serial.print("[WiFi] Connecting...");
  WiFi.begin(ssid, pass);
  while (WiFi.status() != WL_CONNECTED) {
    Serial.println("[WiFi] Connecting to WiFi...");
    delay(1000);
  }
  Serial.println("[WiFi] Connected!");
  Serial.println(WiFi.localIP());
}

// Fungsi Koneksi MQTT
void connectMQTT() {
  String clientId = "ESP8266-" + String(random(0xffff), HEX);
  client.begin(mqttServer, mqttPort, net);
  client.onMessage(messageReceived);
  while (!client.connect(clientId.c_str())) {
    Serial.println("[MQTT] Connecting to MQTT...");
    delay(1000);
  }
  Serial.println("[MQTT] Connected!");
  client.subscribe(topicPergerakan, 0);
}

// Fungsi Menerima Pesan MQTT
void messageReceived(String &topic, String &payload) {
  Serial.println("[MQTT] Message received: " + topic + " - " +
                 payload);
  //  if (topic == topicPergerakan) {
  //    Serial.println("Data diterima");
  //    delay(3000);
  //  }
}

// Fungsi Membaca dan Mengirim Data Sensor
void publishSensorData(float suhu, float kelembaban, float ppm, String statusGerakan) {
  client.publish(topicSuhu, String(suhu));
  client.publish(topicKelembaban, String(kelembaban));
  client.publish(topicAmonia, String(ppm));
  client.publish(topicPergerakan, statusGerakan);
}
