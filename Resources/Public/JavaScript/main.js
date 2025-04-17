var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);
class ApiService {
  static getOptimizedImage(config, width, height) {
    const viewWidth = Math.round(window.innerWidth);
    return fetch(`/-/img/${width}x${height}/${viewWidth}/${config}/`).then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    });
  }
}
class ViewportService {
  static isInViewport(element) {
    const rect = element.getBoundingClientRect();
    const windowHeight = window.innerHeight || document.documentElement.clientHeight;
    const windowWidth = window.innerWidth || document.documentElement.clientWidth;
    return rect.top <= windowHeight + 100 && rect.bottom >= -100 && rect.left <= windowWidth + 100 && rect.right >= -100;
  }
  static whenInViewport(element) {
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
        rootMargin: "100px"
        // Lädt Bilder 100px bevor sie sichtbar werden
      });
      observer.observe(element);
    });
  }
}
const _ImageHandler = class _ImageHandler {
  constructor() {
  }
  static getInstance() {
    if (!_ImageHandler.instance) {
      _ImageHandler.instance = new _ImageHandler();
    }
    return _ImageHandler.instance;
  }
  preloadImage(src) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => resolve();
      img.onerror = () => reject(new Error(`Failed to load image: ${src}`));
      img.src = src;
    });
  }
  removePictureTag(element) {
    const picture = element.closest("picture");
    if (picture && picture.parentNode) {
      picture.parentNode.insertBefore(element, picture);
      picture.remove();
    }
  }
  processImage(element, config, firstLoad) {
    return ViewportService.whenInViewport(element).then(() => {
      const width = Math.round(element.offsetWidth);
      const height = Math.round(element.offsetHeight);
      if (!width || !height) return;
      return ApiService.getOptimizedImage(config, width, height).then((data) => {
        if (!document.body.contains(element)) return;
        element.width = data.attributes.width;
        element.height = data.attributes.height;
        element.style.aspectRatio = (data.aspectRatio[0] || data.attributes.width) + "/" + (data.aspectRatio[1] || data.attributes.height);
        return this.preloadImage(data.attributes.src).then(() => {
          if (document.body.contains(element)) {
            element.style.removeProperty("aspect-ratio");
            if (firstLoad) {
              this.removePictureTag(element);
              firstLoad = false;
            }
            element.src = data.attributes.src;
          }
        });
      });
    }).catch((error) => console.error("Failed to process image:", error));
  }
};
__publicField(_ImageHandler, "instance");
let ImageHandler = _ImageHandler;
class Picturerino {
  constructor(element) {
    __publicField(this, "resizeDebounceTimeout", null);
    __publicField(this, "imageHandler");
    __publicField(this, "element");
    __publicField(this, "config", "");
    this.element = element;
    this.imageHandler = ImageHandler.getInstance();
    this.init();
  }
  handleResize(element, config) {
    if (this.resizeDebounceTimeout) {
      window.clearTimeout(this.resizeDebounceTimeout);
    }
    this.resizeDebounceTimeout = window.setTimeout(() => this.imageHandler.processImage(element, config, false), 250);
  }
  init() {
    this.config = this.element.getAttribute("data-config");
    if (this.config) {
      this.element.removeAttribute("data-config");
      this.element.removeAttribute("onload");
      this.element.removeAttribute("srcset");
      window.addEventListener("resize", () => this.handleResize(this.element, this.config));
    }
    this.imageHandler.processImage(this.element, this.config, true);
  }
  static handle(element) {
    new Picturerino(element);
  }
}
window.Pictureino = {
  handle: (element) => Picturerino.handle(element)
};
