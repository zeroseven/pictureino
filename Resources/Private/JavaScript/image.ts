import {ElementSize, ImageResponse, SourceMap} from './types'
import {Observer} from './observer'
import {Loader} from './loader'

export class Image {
  private element: HTMLImageElement
  private config: string
  private observer: Observer
  private loader: Loader
  private sources: SourceMap
  private webpSupport: boolean
  private size: ElementSize

  constructor(element: HTMLImageElement, config: string) {
    this.element = element
    this.config = config
    this.observer = new Observer(this.element)
    this.loader = new Loader()
    this.sources = {}
    this.webpSupport = false
    this.size = {
      width: this.element.offsetWidth,
      height: this.element.offsetHeight,
    }

    this.observeElement = this.observeElement.bind(this)
    this.init()
  }

  private checkWebpSupport(): void {
    const source = 'data:image/webp;base64,UklGRkoAAABXRUJQVlA4WAoAAAAQAAAAAAAAAAAAQUxQSAwAAAARBxAR/Q9ERP8DAABWUDggGAAAABQBAJ0BKgEAAQAAAP4AAA3AAP7mtQAAAA=='
    const img = document.createElement('img') as HTMLImageElement

    img.onload = (): void => { this.webpSupport = (img.width > 0) && (img.height > 0) }
    img.src = source
  }

  private getRequestUri(): string {
    const webp = this.webpSupport ? 'webp/' : ''
    const width = parseInt(this.size.width.toString(), 10)
    const height = parseInt(this.size.height.toString(), 10)
    const view = Math.round(window.innerWidth)
    const retina = window.devicePixelRatio > 1 ? 2 : 1

    return `/-/pictureino/img/${view}${retina}x${this.config}/${webp}${width}x${height}/`
  }

  private updateImage(imageResponse: ImageResponse): void {
    this.element.width = imageResponse.processed.width
    this.element.height = imageResponse.processed.height

    if (imageResponse.processed.img1x) {
      this.element.src = imageResponse.processed.img1x
    }

    if (imageResponse.processed.img2x) {
      this.element.src = imageResponse.processed.img2x
      this.element.srcset = imageResponse.processed.img2x + ' 2x'
    } else {
      this.element.removeAttribute('srcset')
    }
  }

  private updateSourceTag(view: number, imageResponse: ImageResponse): void {
    const source = this.sources[view]

    if (source) {
      source.width = imageResponse.processed.width
      source.height = imageResponse.processed.height

      if (imageResponse.processed.img1x) {
        source.srcset = imageResponse.processed.img1x
      }

      if (imageResponse.processed.img2x) {
        source.srcset = imageResponse.processed.img2x + ' 2x'
      }
    }
  }

  private getSourceKey(view: number): number {
    const views = Object.keys(this.sources).map(Number)

    if(views.length) {
      const lowerViews = views.filter(value => value <= view)

      return lowerViews.length ? Math.max(...lowerViews) : 0
    }

    return 0
  }

  private updateSource(): void {
    this.element.dataset.loaded = 'false'

    this.loader.requestImage(this.getRequestUri())
      .then((result: ImageResponse) => {
        const sourceKey = this.getSourceKey(result.view)
        sourceKey ? this.updateSourceTag(sourceKey, result) : this.updateImage(result)

        this.element.addEventListener('load', () =>{
          this.element.dataset.loaded = 'true'
          setTimeout(this.observeElement, 1000)
        }, {once: true})
      }).catch(() => {
        this.element.dataset.loaded = 'true'
        setTimeout(this.observeElement, 1000)
      })
  }

  private observeElement(): void {
    this.observer.onResize(size => {
      this.size = size
      this.updateSource()
    }, this.size)
  }

  private init(): void {
    this.checkWebpSupport();

    ['onload', 'srcset'].forEach(attr => {
      this.element.removeAttribute(attr);3
    })

    const picture = this.element.closest('picture') as HTMLPictureElement
    if (picture) {
      Array.prototype.slice.call(picture.getElementsByTagName('source')).forEach((source: HTMLSourceElement) => {
        const mediaAttr = source.getAttribute('media')
        if (mediaAttr) {
          const matches = mediaAttr.match(/\d+/)
          if (matches && matches[0]) {
            const view = parseInt(matches[0], 10)
            if (view) {
              this.sources[view] = source
            }
          }
        }
      })
    }

    this.observer.inView(() => this.updateSource())
  }
}
