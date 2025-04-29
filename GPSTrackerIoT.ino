#include <TinyGPS++.h>
#include <HardwareSerial.h>
#include <ezButton.h>

TinyGPSPlus gps;
HardwareSerial mySerial(2);    // GPS Module pada Serial2
HardwareSerial mySerial2(1);   // GSM Module pada Serial1 (RX 18 TX 19)

unsigned long interv = 30000;   // Interval pengiriman 30 detik
unsigned long prevTimer = 0;
unsigned long prevSMS = 0;
int intervSMS = 10000;

bool emergencyState = false;    // Status keadaan darurat
int buzzer = 21;                // Pin buzzer
ezButton button(22);            // Tombol emergency pada pin 22

double lat = 0.0;               // Variabel untuk menyimpan latitude
double lng = 0.0;               // Variabel untuk menyimpan longitude

// URL Server
const String api_url = "smartracker.web.id";  // Domain server
const String api_path = "/api/gps";           // Endpoint API

void setup() {
    Serial.begin(115200);                
    mySerial.begin(9600, SERIAL_8N1, 16, 17);  // GPS Module
    mySerial2.begin(115200, SERIAL_8N1, 18, 19); // GSM Module
    pinMode(buzzer, OUTPUT);             
    delay(3000);                         

    Serial.println("Initializing system...");

    bool gsmReady = checkGSMReady();
    bool gpsReady = checkGPSReady();

    if (gsmReady) {
        Serial.println("✅ GSM Module Ready");
    } else {
        Serial.println("❌ GSM Module Not Detected!");
    }

    if (gpsReady) {
        Serial.println("✅ GPS Module Ready");
    } else {
        Serial.println("❌ GPS Module Not Detected!");
    }

    if (gsmReady) {
        setupGPRS();
        Serial.println("✅ GPRS Connection Established");
    } else {
        Serial.println("❌ Failed to setup GPRS");
    }

    Serial.println("🔄 System Ready! Starting loop...");
}

void loop() {
    button.loop(); 

    // Baca data GPS
    while (mySerial.available() > 0) {
        char c = mySerial.read();
        Serial.print(c);
        gps.encode(c);

        if (gps.location.isUpdated()) {
            lat = gps.location.lat();
            lng = gps.location.lng();
            Serial.print("Latitude: ");
            Serial.println(lat, 6);
            Serial.print("Longitude: ");
            Serial.println(lng, 6);
        }
    }

    // Cek status GSM sebelum mengirim data ke server
    Serial.println("Checking GSM status...");
    if (!checkGSMReady()) {
        Serial.println("⚠️ GSM tidak terdeteksi! Tidak bisa mengirim data.");
    }

    // Cek status GPS sebelum mengirim data ke server
    Serial.println("Checking GPS status...");
    if (lat == 0.0 && lng == 0.0) {
        Serial.println("⚠️ GPS belum mendapatkan koordinat, menunggu...");
    } else {
        Serial.println("✅ GPS Ready, Latitude: " + String(lat, 6) + ", Longitude: " + String(lng, 6));
    }

    // Kirim data ke server setiap interval waktu
    unsigned long timer = millis();
    if (timer - prevTimer >= interv && !emergencyState) {
        prevTimer = timer;
        if (lat != 0.0 && lng != 0.0) {  // Pastikan GPS valid sebelum kirim
            sendToAPI(lat, lng);
        } else {
            Serial.println("⚠️ Data GPS tidak valid, tidak mengirim ke server.");
        }
    }

    // Logika tombol emergency
    if (button.isPressed()) {
        emergencyState = !emergencyState;
        if (!emergencyState) {
            noTone(buzzer);
        }
    }

    // Aktifkan buzzer jika dalam keadaan darurat
    if (emergencyState) {
        tone(buzzer, 700);
        Serial.println("🚨 Emergency Mode Active!");

        if (lat != 0.0 && lng != 0.0) { // Pastikan koordinat valid sebelum mengirim SMS
            if (timer - prevSMS >= intervSMS) {
                prevSMS = timer;
                String msg = "EMERGENCY! https://www.google.com/maps?q=" + String(lat, 6) + "," + String(lng, 6);
                sendMSG(msg);
            }
        } else {
            Serial.println("⚠️ GPS belum siap, tidak mengirim SMS.");
        }
    }
}

// Cek apakah GSM Module terhubung
bool checkGSMReady() {
    mySerial2.println("AT");
    delay(1000);

    String response = "";
    while (mySerial2.available()) {
        response += (char)mySerial2.read();
    }

    Serial.print("GSM Response: ");
    Serial.println(response);

    return response.indexOf("OK") != -1; // Pastikan ada respon "OK"
}

// Cek apakah GPS Module mendapatkan data
bool checkGPSReady() {
    Serial.println("Checking GPS...");
    unsigned long timeout = millis() + 15000;  // Perpanjang waktu tunggu jadi 15 detik

    while (millis() < timeout) {
        while (mySerial.available() > 0) {
            char c = mySerial.read();
            Serial.print(c); // Debug: Menampilkan data dari GPS
            gps.encode(c);
            if (gps.location.isUpdated()) {
                Serial.println("\n✅ GPS Location Detected!");
                return true;
            }
        }
    }
    Serial.println("\n❌ No GPS Data Received!");
    return false;
}

void setupGPRS() {
    Serial.println("Setting up GPRS...");
    sendCommand("AT+HTTPTERM");
    delay(500);

    sendCommand("AT+SAPBR=3,1,\"CONTYPE\",\"GPRS\"");
    sendCommand("AT+SAPBR=3,1,\"APN\",\"telkomsel\""); 

    sendCommand("AT+SAPBR=1,1");
    delay(1000);
    sendCommand("AT+SAPBR=2,1"); // Periksa apakah koneksi berhasil

    sendCommand("AT+HTTPINIT");
    sendCommand("AT+HTTPSSL=0");
    sendCommand("AT+HTTPPARA=\"CID\",1");
}

void sendToAPI(float latitude, float longitude) {
    if (latitude == 0 || longitude == 0) {
        Serial.println("⚠️ Invalid GPS data. Skipping send.");
        return;
    }

    String jsonData = "{\"latitude\":" + String(latitude, 6) +
                      ",\"longitude\":" + String(longitude, 6) +
                      ",\"emergency\":" + (emergencyState ? "true" : "false") + "}";

    Serial.println("📡 Sending data: " + jsonData);

    sendCommand("AT+SAPBR=2,1");
    sendCommand("AT+CSQ");

    String url = "http://" + api_url + api_path;
    sendCommand("AT+HTTPPARA=\"URL\",\"" + url + "\"");
    sendCommand("AT+HTTPPARA=\"CONTENT\",\"application/json\"");
    sendCommand("AT+HTTPDATA=" + String(jsonData.length()) + ",10000");
    delay(500);
    sendCommand(jsonData);
    delay(2500);
    sendCommand("AT+HTTPACTION=1");
    delay(2500);
    sendCommand("AT+HTTPREAD");
    delay(500);
}

void sendCommand(String command) {
    mySerial2.println(command);
    delay(200);
    while (mySerial2.available()) {
        Serial.write(mySerial2.read());
    }
}

void sendMSG(String message) {
    sendCommand("AT+CMGF=1");  
    sendCommand("AT+CMGS=\"+6283168514464\""); 
    delay(100);
    mySerial2.print(message);
    delay(100);
    mySerial2.write(26);  
    Serial.println("📩 Message Sent!");
}
