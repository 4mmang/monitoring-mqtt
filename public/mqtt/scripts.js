const brokerUrl = 'ws://broker.emqx.io:8083/mqtt';
const topicSuhu = "suhukandang";
const topicAmonia = "amoniakandang";
const topicPergerakan = "pergerakankandang";

const client = new MQTTClient(brokerUrl);
const options = { qos: 1, retain: true };

client.onConnect = () => {
    client.subscribe(topicSuhu);
    client.subscribe(topicAmonia);
    client.subscribe(topicPergerakan);
};

client.onMessage = (topic, message) => {
    console.log('Topic:', topic);
    console.log('Message:', message.toString());
    if (topic === topicAmonia) {
        console.log('Amonia:', message.toString());

    }
    if (topic === topicSuhu) {
        console.log('Suhu:', message.toString());
    }

    if (topic === topicPergerakan) {
        console.log('Pergerakan:', message.toString());
    }
};

client.connect();