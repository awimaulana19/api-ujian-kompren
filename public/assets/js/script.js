const video = document.getElementById("vid");
const warningMessage = document.getElementById("warning-message");
const form = document.querySelector("form");

Promise.all([faceapi.nets.tinyFaceDetector.loadFromUri("/models")]).then(
    startVideo
);

function startVideo() {
    navigator.getUserMedia(
        { video: {} },
        (stream) => (video.srcObject = stream),
        (err) => alert(`Anda telah melanggar aturan, Harap Izinkan Kamera.`, form.submit())
    );
}

let counter3 = 0;
const maxCount3 = 5;

video.addEventListener("play", () => {
    setInterval(async () => {
        const detections = await faceapi.detectAllFaces(
            video,
            new faceapi.TinyFaceDetectorOptions()
        );

        const numFaces = detections.length;
        if (numFaces < 2 || numFaces > 2) {
            counter3++;
            if (counter3 === maxCount3) {
                form.submit();
            } else if (counter3 < maxCount3) {
                warningMessage.classList.add("alert", "alert-danger");
                warningMessage.textContent =
                    "Anda telah melanggar aturan, Wajah terdeteksi kurang atau lebih dari 2 orang. Jika Anda melakukan pelanggaran berulang kali, jawaban akan tersubmit.";
                setTimeout(function () {
                    warningMessage.classList.remove("alert", "alert-danger");
                    warningMessage.textContent = "";
                }, 6000);
            }
        }
    }, 7000);
});
