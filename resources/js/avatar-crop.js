window.avatarCropper = function () {
    return {
        active: false,
        zoom: 1,
        offsetX: 0,
        offsetY: 0,
        dragging: false,
        startX: 0,
        startY: 0,
        img: null,
        size: 260,

        onFileSelected(event) {
            const file = event.target.files[0];

            if (!file) {
                return;
            }

            const url = URL.createObjectURL(file);
            this.img = new Image();
            this.img.onload = () => {
                this.active = true;
                this.zoom = 1;
                this.offsetX = 0;
                this.offsetY = 0;
                this.$nextTick(() => this.draw());
            };
            this.img.src = url;
        },

        cancel() {
            this.active = false;
            this.img = null;
            this.$refs.fileInput.value = '';
        },

        draw() {
            const canvas = this.$refs.canvas;
            const ctx = canvas.getContext('2d');
            const size = this.size;
            canvas.width = size;
            canvas.height = size;
            ctx.clearRect(0, 0, size, size);

            const scale = Math.max(size / this.img.width, size / this.img.height) * this.zoom;
            const w = this.img.width * scale;
            const h = this.img.height * scale;
            const x = (size - w) / 2 + this.offsetX;
            const y = (size - h) / 2 + this.offsetY;

            ctx.save();
            ctx.beginPath();
            ctx.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
            ctx.clip();
            ctx.drawImage(this.img, x, y, w, h);
            ctx.restore();
        },

        onZoom() {
            this.$nextTick(() => this.draw());
        },

        startDrag(event) {
            this.dragging = true;
            const point = event.touches ? event.touches[0] : event;
            this.startX = point.clientX - this.offsetX;
            this.startY = point.clientY - this.offsetY;
        },

        onDrag(event) {
            if (!this.dragging) {
                return;
            }

            const point = event.touches ? event.touches[0] : event;
            this.offsetX = point.clientX - this.startX;
            this.offsetY = point.clientY - this.startY;
            this.draw();
        },

        stopDrag() {
            this.dragging = false;
        },

        confirm() {
            const dataUrl = this.$refs.canvas.toDataURL('image/png');
            this.$wire.saveCroppedAvatar(dataUrl);
            this.active = false;
            this.img = null;
            this.$refs.fileInput.value = '';
        },
    };
};
