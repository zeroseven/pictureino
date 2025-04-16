export class ViewportService {
    private static isInViewport(element: HTMLElement): boolean {
        const rect = element.getBoundingClientRect();
        const windowHeight = window.innerHeight || document.documentElement.clientHeight;
        const windowWidth = window.innerWidth || document.documentElement.clientWidth;

        // Element ist teilweise im Viewport oder 100px davor/danach
        return (
            rect.top <= windowHeight + 100 &&
            rect.bottom >= -100 &&
            rect.left <= windowWidth + 100 &&
            rect.right >= -100
        );
    }

    static whenInViewport(element: HTMLElement): Promise<void> {
        return new Promise((resolve) => {
            if (this.isInViewport(element)) {
                resolve();
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    observer.disconnect();
                    resolve();
                }
            }, {
                rootMargin: '100px' // Lädt Bilder 100px bevor sie sichtbar werden
            });

            observer.observe(element);
        });
    }
}
