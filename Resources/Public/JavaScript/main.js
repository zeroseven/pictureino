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
class ImageHandler {
  static preloadImage(src) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => resolve();
      img.onerror = () => reject(new Error(`Failed to load image: ${src}`));
      img.src = src;
    });
  }
  static removePictureTag(element) {
    const picture = element.closest("picture");
    if (picture && picture.parentNode) {
      picture.parentNode.insertBefore(element, picture);
      picture.remove();
    }
  }
  static processImage(element, config, firstLoad) {
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
}
class Picturerino {
  static handleResize(element, config) {
    if (this.resizeDebounceTimeout) {
      window.clearTimeout(this.resizeDebounceTimeout);
    }
    this.resizeDebounceTimeout = window.setTimeout(() => ImageHandler.processImage(element, config, false), 250);
  }
  static init(element) {
    const config = element.getAttribute("data-config");
    if (config) {
      element.removeAttribute("data-config");
      element.removeAttribute("onload");
      element.removeAttribute("srcset");
      window.addEventListener("resize", () => this.handleResize(element, config));
      return config;
    }
    return null;
  }
  static handle(element) {
    const config = this.init(element);
    if (config) {
      this.list.push(element);
      ImageHandler.processImage(element, config, true);
    }
  }
}
__publicField(Picturerino, "list", []);
__publicField(Picturerino, "resizeDebounceTimeout", null);
window.Picturerino = Picturerino;
