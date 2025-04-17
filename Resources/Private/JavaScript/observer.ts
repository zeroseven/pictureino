export class Observer {
  private element: Element;
  private resizeObserver: ResizeObserver | null = null;
  private intersectionObserver: IntersectionObserver | null = null;
  private resizeTimeout: number | null = null;
  private readonly observerOptions = {
    threshold: 0.1,
    rootMargin: '0px'
  };

  constructor(element: Element) {
    this.element = element;
  }

  public inView(): Promise<IntersectionObserverEntry> {
    return new Promise((resolve) => {
      this.intersectionObserver?.disconnect();

      this.intersectionObserver = new IntersectionObserver((entries) => {
        const entry = entries[0];
        if (entry.isIntersecting) {
          resolve(entry);
          this.observeIntersection();
        }
      }, this.observerOptions);

      this.intersectionObserver.observe(this.element);
    });
  }

  public resize(): Promise<ResizeObserverEntry> {
    return new Promise((resolve) => {
      this.resizeObserver?.disconnect();

      this.resizeObserver = new ResizeObserver((entries) => {
        this.throttle(() => {
          resolve(entries[0]);
          this.observeResize();
        });
      });

      this.resizeObserver.observe(this.element);
    });
  }

  private throttle(callback: () => void): void {
    if (this.resizeTimeout) {
      window.clearTimeout(this.resizeTimeout);
    }

    this.resizeTimeout = window.setTimeout(() => {
      callback();
      this.resizeTimeout = null;
    }, 150);
  }

  private observeIntersection(): void {
    this.intersectionObserver = new IntersectionObserver(
      (entries) => entries[0].isIntersecting && this.inView().catch(console.error),
      this.observerOptions
    );
    this.intersectionObserver.observe(this.element);
  }

  private observeResize(): void {
    this.resizeObserver = new ResizeObserver(() => {
      this.throttle(() => this.resize().catch(console.error));
    });
    this.resizeObserver.observe(this.element);
  }

  public disconnect(): void {
    if (this.resizeTimeout) {
      window.clearTimeout(this.resizeTimeout);
      this.resizeTimeout = null;
    }
    this.resizeObserver?.disconnect();
    this.intersectionObserver?.disconnect();
  }
}
