<template>
    <div class="blob-container">
        <svg
            viewBox="0 0 1000 1000"
            xmlns="http://www.w3.org/2000/svg"
            class="blob-svg"
        >
            <defs>
                <linearGradient id="blob-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color: rgba(255, 165, 0, 0.1)" /> <!-- Light orange -->
                    <stop offset="100%" style="stop-color: rgba(255, 223, 0, 0.1)" /> <!-- Light yellow -->
                </linearGradient>
            </defs>
            <path
                fill="url(#blob-gradient)"
                d="M833.5,318Q766,636,438,754.5Q110,873,115.5,436.5Q121,0,438.5,69Q756,138,833.5,318Z"
            />
        </svg>
    </div>
</template>

<style scoped>
.blob-container {
    position: fixed;
    top: -20%;
    left: -20%;
    width: 100%;
    height: 100%;
    z-index: 0;
    pointer-events: none;
}

.blob-svg {
    width: 100%;
    height: 100%;
    filter: blur(80px);
    opacity: 0.7;
    transform: scale(1.5);
}
</style>
