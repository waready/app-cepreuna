<template>
    <div>
        <qrcode-stream :camera="camera" @decode="onDecode" @init="onInit"> </qrcode-stream>
    </div>
</template>

<script>
import { QrcodeStream } from "vue3-qrcode-reader";
export default {
    components: { QrcodeStream },

    data() {
        return {
            isValid: undefined,
            camera: "auto",
            result: null,
        };
    },

    // computed: {
    //     validationPending() {
    //         return this.isValid === undefined && this.camera === "off";
    //     },

    //     validationSuccess() {
    //         return this.isValid === true;
    //     },

    //     validationFailure() {
    //         return this.isValid === false;
    //     },
    // },

    methods: {
        onInit(promise) {
            promise.catch(console.error).then(this.resetValidationState);
        },

        resetValidationState() {
            this.isValid = undefined;
        },

        async onDecode(content) {
            this.result = content;
            this.turnCameraOff();

            // pretend it's taking really long
            await this.timeout(2000);
            this.$emit("scan-qr", content);
            // this.isValid = content.startsWith("http");

            // some more delay, so users have time to read the message
            await this.timeout(2000);

            this.turnCameraOn();
        },

        turnCameraOn() {
            this.camera = "auto";
        },

        turnCameraOff() {
            this.camera = "off";
        },

        timeout(ms) {
            return new Promise((resolve) => {
                window.setTimeout(resolve, ms);
            });
        },
    },
};
</script>

<style scoped>
.validation-success,
.validation-failure,
.validation-pending {
    position: absolute;
    width: 100%;
    height: 100%;

    background-color: rgba(255, 255, 255, 0.8);
    text-align: center;
    font-weight: bold;
    font-size: 1.4rem;
    padding: 10px;

    display: flex;
    flex-flow: column nowrap;
    justify-content: center;
}
.validation-success {
    color: green;
}
.validation-failure {
    color: red;
}
</style>
