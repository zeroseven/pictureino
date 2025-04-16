var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);
class ApiService {
  static async fetchImageUrl(config, width, height) {
    const response = await fetch(`/-/img/${width}x${height}/${config}/`);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
  }
  static async getOptimizedImageUrl(config, width, height) {
    try {
      const data = await this.fetchImageUrl(config, width, height);
      return data.src;
    } catch (error) {
      console.error("Failed to fetch optimized image:", error);
      throw error;
    }
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
class ImageHandler {
  static async preloadImage(src) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => resolve();
      img.onerror = () => reject(new Error(`Failed to load image: ${src}`));
      img.src = src;
    });
  }
  static updateImageSource(image, src) {
    if (image instanceof HTMLSourceElement) {
      image.srcset = src;
    } else {
      image.src = src;
    }
  }
  static findTargetElement(element) {
    const picture = element.closest("picture");
    if (picture) {
      const sources = Array.from(picture.getElementsByTagName("source"));
      return sources[0] || element;
    }
    return element;
  }
  static async processImage(image) {
    try {
      await ViewportService.whenInViewport(image.element);
      const width = Math.round(image.element.offsetWidth);
      const height = Math.round(image.element.offsetHeight);
      if (width === 0 || height === 0) {
        return;
      }
      const newSrc = await ApiService.getOptimizedImageUrl(image.config, width, height);
      await this.preloadImage(newSrc);
      if (document.body.contains(image.element)) {
        const targetElement = this.findTargetElement(image.element);
        this.updateImageSource(targetElement, newSrc);
      }
    } catch (error) {
      console.error("Failed to process image:", error);
    }
  }
}
class Picturerino {
  static handleResize() {
    if (this.resizeDebounceTimeout) {
      window.clearTimeout(this.resizeDebounceTimeout);
    }
    this.resizeDebounceTimeout = window.setTimeout(() => {
      this.images.forEach((image) => {
        if (document.body.contains(image.element)) {
          ImageHandler.processImage(image);
        } else {
          this.images.delete(image.element);
        }
      });
    }, 250);
  }
  /**
   * Initialisiert das Picturerino System
   */
  static init() {
    if (this.isInitialized) return;
    window.addEventListener("resize", () => this.handleResize());
    this.isInitialized = true;
  }
  /**
   * Verarbeitet ein einzelnes Bildelement
   * @param element Das zu verarbeitende Bildelement
   */
  static handle(element) {
    if (!this.isInitialized) {
      this.init();
    }
    const config = element.getAttribute("data-config");
    if (!config) {
      console.error("No data-config attribute found on image element");
      return;
    }
    const imageConfig = { element, config };
    this.images.set(element, imageConfig);
    ImageHandler.processImage(imageConfig);
  }
}
__publicField(Picturerino, "images", /* @__PURE__ */ new Map());
__publicField(Picturerino, "resizeDebounceTimeout", null);
__publicField(Picturerino, "isInitialized", false);
document.addEventListener("DOMContentLoaded", () => {
  Picturerino.init();
});
window.Picturerino = Picturerino;
