<script setup>
import { onMounted, ref } from 'vue';

const canvas = ref(null);
const video = ref(null);
const ctx = ref(null);

const constraints = ref({
    audio: false,
    video: true
});

onMounted(async () => {
    
    if (video.value && canvas.value) {
        ctx.value = canvas.value.getContext("2d");

        await navigator.mediaDevices.getUserMedia(constraints.value).then(setStream).catch(e => {
            console.error(e);
        });
    }

});

function setStream(stream) {
    video.value.srcObject = stream;

    video.value.play();

    requestAnimationFrame(Draw);
}

function Draw() {
    ctx.value.drawImage(video.value, 0, 0, canvas.value.width, canvas.value.height);

    requestAnimationFrame(Draw);
}

function takePicture() {
    let link = document.createElement('a');

    link.download = `vue-cam-${new Date().toISOString()}.png`;

    link.href = canvas.value.toDataURL();

    link.click();
}

</script>
<template>
    <div>
        <video ref="video" autoplay playsinline webkit-playsinline muted hidden></video>

        <canvas ref="canvas" width="1280" height="720" class="rounded-3xl bg-black shadow-2xl shadow-slate-900/20"></canvas>

        <div class="flex items-center justify-center py-4">
            <button @click="takePicture" class="rounded-2xl bg-emerald-600 px-6 py-3 text-base font-semibold uppercase tracking-wide text-white transition-colors hover:bg-emerald-700">
                Capturar imagem
            </button>
        </div>
    </div>
</template>
