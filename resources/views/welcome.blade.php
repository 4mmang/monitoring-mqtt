    <!doctype html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Monitoring Kandang Kambing</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
            integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    </head>

    <body class="">
        <div class="container mb-5">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <h1 class="text-center mt-5">Monitoring <span class="">Kandang Kambing</span></h1>
                    <p class="text-center fs-4">Selamat datang di sistem monitoring kandang kambing.</p>
                </div>
                <div class="col-md-6 text-center">
                    <div class="card mt-4 p-3 shadow-lg">
                        <div class="card-body">
                            <h3 class="card-title">Suhu Saat Ini</h5>
                                <p class="card-text fs-1" id="suhu">0° C</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-center">
                    <div class="card mt-4 p-3 shadow-lg">
                        <div class="card-body">
                            <h3 class="card-title">Kelembaban Saat Ini</h5>
                                <p class="card-text fs-1" id="kelembaban">0 %</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-center">
                    <div class="card mt-4 p-3 shadow-lg">
                        <div class="card-body">
                            <h3 class="card-title">Kadar Amonia Saat Ini</h3>
                            <p class="card-text fs-1" id="ppm">0 PPM</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-center">
                    <div class="card mt-4 p-3 shadow-lg">
                        <div class="card-body">
                            <h2 class="card-title">Pergerakan</h2>
                            <p class="card-text fs-3 mt-4" id="gerakan">Tidak Ada Pergerakan</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous">
        </script>
        <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
        <script>
            // const client = mqtt.connect('wss://broker.emqx.io:8084/mqtt');
            // const client = mqtt.connect('wss://test.mosquitto.org:8081/mqtt');
            const client = mqtt.connect('wss://broker.hivemq.com:8884/mqtt');
            const topicSuhu = "suhukandang";
            const topicKelembaban = "kelembabankandang";
            const topicAmonia = "amoniakandang";
            const topicPergerakan = "pergerakankandang";

            let suhu = "0";
            let kelembaban = "0";
            let gasAmonia = "0";
            let gerakan = "off";

            client.on('connect', () => {
                console.log("[MQTT] Connected to broker (WebSocket)");

                // Subscribe ke semua topik yang dibutuhkan
                client.subscribe([topicSuhu, topicKelembaban, topicAmonia, topicPergerakan], function(err) {
                    if (!err) {
                        console.log("[MQTT] Subscribed to all topics.");
                    } else {
                        console.error("[MQTT] Subscription error:", err);
                    }
                });
            });

            client.on('message', function(topic, message) {
                const msgString = message.toString();
                console.log(`[MQTT] ${topic}: ${msgString}`);

                if (topic === topicSuhu) {
                    suhu = msgString;
                    document.getElementById("suhu").innerText = msgString + "° C";
                }

                if (topic === topicKelembaban) {
                    kelembaban = msgString;
                    document.getElementById("kelembaban").innerText = msgString + "%";
                }

                if (topic === topicAmonia) {
                    gasAmonia = msgString;
                    document.getElementById("ppm").innerText = msgString + " PPM";
                }

                if (topic === topicPergerakan) {
                    gerakan = msgString;
                    const statusGerakan = msgString === 'on' ? 'Ada Pergerakan' : 'Tidak Ada Pergerakan';
                    document.getElementById("gerakan").innerText = statusGerakan;
                }

                fetch('/store', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            suhu: suhu,
                            kelembaban: kelembaban,
                            gas_amonia: gasAmonia,
                            gerakan: gerakan
                        })
                    })
                    .then(response => {
                        if (response.ok) {
                            console.log("Data stored successfully.");
                        } else {
                            console.error("Error storing data:", response.statusText);
                        }
                    })
                    .catch(error => {
                        console.error("Fetch error:", error);
                    });
            });
        </script>
    </body>

    </html>
