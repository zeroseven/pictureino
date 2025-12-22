import {ElementSize} from './types'

type ResizeCallback = (size: ElementSize, observer: Observer) => void;
type ViewCallback = (observer: Observer) => void;

export class Observer {
  private element: Element
  private resizeObserver: ResizeObserver | null = null
  private intersectionObserver: IntersectionObserver | null = null
  private resizeTimeout: number | null = null
  private lastSize: ElementSize | null = null
  private resizeCallback: ResizeCallback | null = null

  constructor(element: Element) {
    this.element = element
    this._handleResize = this._handleResize.bind(this)
  }

  private _handleResize(entries: ResizeObserverEntry[]): void {
    if (!this.resizeCallback) return

    const entry = entries[0]
    const newSize: ElementSize = {
      width: entry.contentRect.width,
      height: entry.contentRect.height,
    }

    if (this.resizeTimeout) {
      window.clearTimeout(this.resizeTimeout)
    }

    this.resizeTimeout = window.setTimeout(() => {
      if (
        this.lastSize &&
        this.lastSize.width > 0 &&
        this.lastSize.height > 0 &&
        Math.abs(this.lastSize.width - newSize.width) / this.lastSize.width <= 0.02 &&
        Math.abs(this.lastSize.height - newSize.height) / this.lastSize.height <= 0.02
      ) {
        return
      }

      this.lastSize = newSize
      this.resizeCallback!(newSize, this)
    }, 150)
  }

  public onResize(callback: ResizeCallback): void {
    this.resizeCallback = callback

    if (!this.resizeObserver) {
      this.resizeObserver = new ResizeObserver(this._handleResize)
      this.resizeObserver.observe(this.element)
    }
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
      {threshold: 0.1, rootMargin: '0px'},
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
    this.lastSize = null
    this.resizeCallback = null
  }
}
