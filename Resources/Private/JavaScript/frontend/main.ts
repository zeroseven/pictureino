import {Image} from './image'

class PictureinoWrap extends HTMLElement {
  constructor() {
    super()
  }

  connectedCallback(): void {
    const images: HTMLCollectionOf<HTMLImageElement>  = this.getElementsByTagName('img')
    const config: string = this.getAttribute('data-config') || ''

    if (images.length === 1 && config) {
      new Image(images[0], config, this)
    }
  }
}

customElements.define('pictureino-wrap', PictureinoWrap)
