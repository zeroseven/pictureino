import {ElementSize, ImageResponse, SourceMap} from './types'
import {Observer} from './observer'
import {Loader} from './loader'

const webpSupport= (() : Promise<boolean> => {
  return new Promise<boolean>(resolve => {
    const img = new Image()
    img.onload = (): void => resolve(img.width > 0 && img.height > 0)
    img.onerror = (): void => resolve(false)
    img.src = 'data:image/webp;base64,UklGRkoAAABXRUJQVlA4WAoAAAAQAAAAAAAAAAAAQUxQSAwAAAARBxAR/Q9ERP8DAABWUDggGAAAABQBAJ0BKgEAAQAAAP4AAA3AAP7mtQAAAA=='
  })
})()

class PictureinoWrap extends HTMLElement {
  private image!: HTMLImageElement
  private config!: string
  private observer!: Observer
  private loader!: Loader
  private sources!: SourceMap
  private size!: ElementSize

  constructor() {
    super()

    this.updateSource = this.updateSource.bind(this)
  }

  private async getRequestUri(): Promise<string> {
    const webp = await webpSupport ? 'webp/' : ''
    const width = parseInt(this.size.width.toString(), 10)
    const height = parseInt(this.size.height.toString(), 10)
    const view = Math.round(window.innerWidth)
    const retina = window.devicePixelRatio > 1 ? 2 : 1

    return `/-/pictureino/img/${view}${retina}x${this.config}/${webp}${width}x${height}/`
  }

  private updateImage(imageResponse: ImageResponse): void {
    this.image.width = imageResponse.processed.width
    this.image.height = imageResponse.processed.height

    if (imageResponse.processed.img1x) {
      this.image.src = imageResponse.processed.img1x
    }

    if (imageResponse.processed.img2x) {
      this.image.src = imageResponse.processed.img2x
      this.image.srcset = imageResponse.processed.img2x + ' 2x'
    } else {
      this.image.removeAttribute('srcset')
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
    const loaded = (): void => {
      delete this.dataset.loading

      this.observer.onResize(size => {
        this.size = size
        this.updateSource()
      }, this.size)
    }

    // If the image is narrower than 50px or has no height, we can keeep its fallback image
    if (this.size.width <= 50 || this.size.height <= 0) {
      return loaded()
    }

    this.dataset.loading = ''

    this.getRequestUri().then((uri: string) => {
      this.loader.requestImage(uri).then((result: ImageResponse) => {
        const sourceKey = this.getSourceKey(result.view)
        sourceKey ? this.updateSourceTag(sourceKey, result) : this.updateImage(result)

        this.image.addEventListener('load', loaded, {once: true})
      }).catch(error => {
        console.info('Pictureino error (retry after 1s)', error)
        setTimeout(loaded, 1000)
      })
    })
  }

  private collectSources(): void {
    const picture = this.image.closest('picture') as HTMLPictureElement

    if (picture) {
      Array.from(picture.getElementsByTagName('source')).forEach((source: HTMLSourceElement) => {
        const mediaAttr = source.getAttribute('media')
        const matches = mediaAttr?.match(/\d+/)
        const view = matches ? parseInt(matches[0], 10) : null

        if (view) {
          this.sources[view] = source
        }
      })
    }
  }

  init(image: HTMLImageElement, config: string): void {
    this.image = image
    this.config = config
    this.observer = new Observer(image)
    this.loader = new Loader()
    this.sources = {}
    this.size = {
      width: image.offsetWidth,
      height: image.offsetHeight,
    }

    this.dataset.loading = ''
    delete this.dataset.config

    this.collectSources()
    this.observer.inView(this.updateSource)
  }

  connectedCallback(): void {
    const images: HTMLCollectionOf<HTMLImageElement>  = this.getElementsByTagName('img')
    const config: string = this.getAttribute('data-config') || ''

    if (images.length === 1 && config) {
      this.init(images[0], config)
    } else {
      console.warn('PictureinoWrap: Invalid configuration. Expected one <img> element and a "data-config" attribute.')
    }
  }

  disconnectedCallback(): void {
    if (this.observer) {
      this.observer.disconnect()
    }
  }
}

customElements.define('pictureino-wrap', PictureinoWrap)
