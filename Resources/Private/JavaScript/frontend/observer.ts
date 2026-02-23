import {ElementSize} from './types'

type ResizeCallback = (size: ElementSize, observer: Observer) => void;
type ViewCallback = (observer: Observer) => void;

export class Observer {
  private element: Element
  private resizeObserver: ResizeObserver | null = null
  private intersectionObserver: IntersectionObserver | null = null
  private resizeTimeout: number | null = null
  private readonly rootMargin: string

  constructor(element: Element, rootMargin = '0px') {
    this.element = element
    this.rootMargin = rootMargin
  }

  public onResize(callback: ResizeCallback, size?: ElementSize): void {
    this.resizeObserver?.disconnect()

    this.resizeObserver = new ResizeObserver(entries => {
      if (this.resizeTimeout) {
        window.clearTimeout(this.resizeTimeout)
      }

      const entry = entries[0]

      this.resizeTimeout = window.setTimeout(() => {
        if (
          size &&
          Math.abs(size.width - entry.contentRect.width) / size.width <= 0.02 &&
          Math.abs(size.height - entry.contentRect.height) / size.height <= 0.02
        ) {
          return
        }

        this.resizeObserver?.disconnect()

        typeof callback === 'function' && callback({
          width: entry.contentRect.width,
          height: entry.contentRect.height,
        }, this)
      }, 150)
    })

    this.resizeObserver.observe(this.element)
  }

  public inView(callback: ViewCallback): void {
    this.intersectionObserver?.disconnect()

    this.intersectionObserver = new IntersectionObserver(
      entries => {
        if (entries[0].isIntersecting) {
          callback(this)
          this.intersectionObserver?.disconnect()
        }
      },
      {threshold: 0.1, rootMargin: this.rootMargin},
    )

    this.intersectionObserver.observe(this.element)
  }

  public disconnect(): void {
    if (this.resizeTimeout) {
      window.clearTimeout(this.resizeTimeout)
      this.resizeTimeout = null
    }
    this.resizeObserver?.disconnect()
    this.intersectionObserver?.disconnect()
  }
}
