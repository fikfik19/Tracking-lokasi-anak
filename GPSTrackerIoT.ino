#include <TinyGPS++.h>
#include <HardwareSerial.h>
#include <ezButton.h>

TinyGPSPlus gps;
HardwareSerial mySerial(2);    // GPS Module pada Serial2
HardwareSerial mySerial2(1);   // GSM Module pada Serial1 (RX 18 TX 19)

unsigned long interv = 20000;   // Interval pengiriman 20 detik
unsigned long prevTimer = 0;

bool emergencyState = false;    // Status keadaan darurat
int buzzer = 21;                // Pin buzzer
ezButton button(22);            // Tombol emergency pada pin 22

double lat;                     // Variabel untuk menyimpan latitude
double lng;                     // Variabel untuk menyimpan longitude

// URL Server
const String api_url = "smartracker.web.id"; // Domain server
const String api_path = "/api/gps";      // Endpoint API

void setup() {
    Serial.begin(115200);                // Inisialisasi Serial Monitor
    mySerial.begin(9600, SERIAL_8N1, 16, 17);  // Inisialisasi GPS Module
    mySerial2.begin(115200, SERIAL_8N1, 18, 19); // Inisialisasi GSM Module
    pinMode(buzzer, OUTPUT);             // Set pin buzzer sebagai output
    delay(3000);                         // Jeda awal

    Serial.println("Initializing...");

    // Reset modul GSM
    mySerial2.println("AT&F");
    updateSerial();

    // Test AT Command
    mySerial2.println("AT");
    updateSerial();

    // Nonaktifkan HTTPS
    mySerial2.println("AT+HTTPSSL=0"); // Nonaktifkan SSL
    updateSerial();

    // Setup GPRS
    setupGPRS();

    Serial.println("System Ready!"); // Pesan bahwa sistem siap
}

void loop() {
    button.loop(); // Update status tombol

    // Baca data GPS
    while (mySerial.available() > 0) {
        char c = mySerial.read();   // Baca data dari GPS
        Serial.print(c);            // Tampilkan data di Serial Monitor
        gps.encode(c);              // Decode data GPS menggunakan TinyGPS++

        // Jika data lokasi valid, simpan latitude dan longitude
        if (gps.location.isUpdated()) {
            lat = gps.location.lat();
            lng = gps.location.lng();
            Serial.print("Latitude: ");
            Serial.println(lat, 6); // Tampilkan latitude dengan 6 digit desimal
            Serial.print("Longitude: ");
            Serial.println(lng, 6); // Tampilkan longitude dengan 6 digit desimal
        }
    }

    // Kirim data ke server setiap interval waktu
    unsigned long timer = millis();
    if (timer - prevTimer >= interv) {
        prevTimer = timer;
        sendToAPI(lat, lng); // Kirim data GPS ke server
    }

    // Logika tombol emergency
    if (button.isPressed()) {
        emergencyState = !emergencyState; // Toggle status emergency
        if (!emergencyState) {
            noTone(buzzer); // Matikan buzzer jika emergency dinonaktifkan
        }
    }

    // Aktifkan buzzer jika dalam keadaan darurat
    if (emergencyState) {
        tone(buzzer, 700); // Bunyikan buzzer dengan frekuensi 700 Hz
        Serial.println("Emergency Mode Active!"); // Debugging
    }
}

void setupGPRS() {
    // Reset HTTP
    mySerial2.println("AT+HTTPTERM");
    updateSerial();
    delay(1000);

    // Setup GPRS - Sesuaikan APN dengan provider
    mySerial2.println("AT+SAPBR=3,1,\"CONTYPE\",\"GPRS\"");
    updateSerial();
    mySerial2.println("AT+SAPBR=3,1,\"APN\",\"telkomsel\""); // Ganti dengan APN provider Anda
    updateSerial();
    mySerial2.println("AT+SAPBR=1,1");
    updateSerial();
    delay(2000);

    // Init HTTP tanpa SSL
    mySerial2.println("AT+HTTPINIT");
    updateSerial();
    mySerial2.println("AT+HTTPSSL=0"); // Pastikan SSL dinonaktifkan
    updateSerial();
    mySerial2.println("AT+HTTPPARA=\"CID\",1");
    updateSerial();
}

void sendToAPI(float latitude, float longitude) {
    // Cek jika latitude dan longitude bernilai 0 (invalid)
    if (latitude == 0 || longitude == 0) {
        Serial.println("Invalid GPS data. Skipping send.");
        return;
    }

    // Format data JSON
    String jsonData = "{\"latitude\":" + String(latitude, 6) +
                     ",\"longitude\":" + String(longitude, 6) +
                     ",\"emergency\":" + String(emergencyState ? "true" : "false") + "}";
    Serial.println("Sending data: " + jsonData);

    // Cek koneksi GPRS
    mySerial2.println("AT+SAPBR=2,1");
    updateSerial();

    // Cek kualitas sinyal
    mySerial2.println("AT+CSQ");
    updateSerial();

    // Set URL dengan http://
    String url = "http://" + api_url + api_path; // Ganti https:// dengan http://
    mySerial2.println("AT+HTTPPARA=\"URL\",\"" + url + "\"");
    updateSerial();
    delay(500);

    // Set content type JSON
    mySerial2.println("AT+HTTPPARA=\"CONTENT\",\"application/json\"");
    updateSerial();
    delay(500);

    // Kirim data
    mySerial2.println("AT+HTTPDATA=" + String(jsonData.length()) + ",10000");
    updateSerial();
    delay(500);

    mySerial2.println(jsonData);
    updateSerial();
    delay(3000);

    // POST request
    mySerial2.println("AT+HTTPACTION=1");
    updateSerial();
    delay(3000);

    // Baca respon dari server
    mySerial2.println("AT+HTTPREAD");
    updateSerial();
    delay(1000);

    Serial.println("Data sent successfully");
}

void updateSerial() {
    delay(500);
    while (Serial.available()) {
        mySerial2.write(Serial.read()); // Kirim data dari Serial Monitor ke GSM Module
    }
    while (mySerial2.available()) {
        Serial.write(mySerial2.read()); // Kirim data dari GSM Module ke Serial Monitor
    }
}
