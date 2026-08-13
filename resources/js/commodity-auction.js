import Alpine from 'alpinejs';

/**
 * Alpine countdown helper for live auction cards.
 */
Alpine.data('auctionCountdown', (endsAt) => ({
    display: '00:00:00',
    endsAt,
    timer: null,
    start() {
        this.tick();
        this.timer = setInterval(() => this.tick(), 1000);
    },
    tick() {
        const end = new Date(this.endsAt).getTime();
        const diff = Math.max(0, end - Date.now());
        const hours = Math.floor(diff / 3600000);
        const minutes = Math.floor((diff % 3600000) / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);

        this.display = [hours, minutes, seconds]
            .map((n) => String(n).padStart(2, '0'))
            .join(':');
    },
}));
