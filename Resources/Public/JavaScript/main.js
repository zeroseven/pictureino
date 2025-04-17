var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);
class Observer {
  constructor(element) {
    __publicField(this, "element");
    __publicField(this, "resizeObserver", null);
    __publicField(this, "intersectionObserver", null);
    __publicField(this, "resizeTimeout", null);
    this.element = element;
  }
  onResize(callback, size) {
    var _a;
    (_a = this.resizeObserver) == null ? void 0 : _a.disconnect();
    this.resizeObserver = new ResizeObserver((entries) => {
      if (this.resizeTimeout) {
        window.clearTimeout(this.resizeTimeout);
      }
      this.resizeTimeout = window.setTimeout(() => {
        var _a2;
        if (size && size.width === entries[0].contentRect.width && size.height === entries[0].contentRect.height) {
          return;
        }
        const entry = entries[0];
        callback({
          width: entry.contentRect.width,
          height: entry.contentRect.height
        }, this);
        (_a2 = this.resizeObserver) == null ? void 0 : _a2.disconnect();
      }, 150);
    });
    this.resizeObserver.observe(this.element);
  }
  inView(callback) {
    var _a;
    (_a = this.intersectionObserver) == null ? void 0 : _a.disconnect();
    this.intersectionObserver = new IntersectionObserver(
      (entries) => {
        var _a2;
        if (entries[0].isIntersecting) {
          callback(this);
          (_a2 = this.intersectionObserver) == null ? void 0 : _a2.disconnect();
        }
      },
      { threshold: 0.1, rootMargin: "0px" }
    );
    this.intersectionObserver.observe(this.element);
  }
  disconnect() {
    var _a, _b;
    if (this.resizeTimeout) {
      window.clearTimeout(this.resizeTimeout);
      this.resizeTimeout = null;
    }
    (_a = this.resizeObserver) == null ? void 0 : _a.disconnect();
    (_b = this.intersectionObserver) == null ? void 0 : _b.disconnect();
  }
}
class Loader {
  constructor() {
    __publicField(this, "cache");
    this.cache = /* @__PURE__ */ new Map();
  }
  preloadImage(url) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.onerror = () => reject(new Error("Fehler beim Laden des Bildes"));
      img.src = url;
    });
  }
  requestImage(url) {
    if (this.cache.has(url)) {
      return Promise.resolve(this.cache.get(url));
    }
    return fetch(url).then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    }).then((config) => {
      this.cache.set(url, config);
      return config;
    }).catch((error) => {
      throw new Error(`Fehler beim Laden der Konfiguration: ${error}`);
    });
  }
  clearCache() {
    this.cache.clear();
  }
  removeFromCache(url) {
    this.cache.delete(url);
  }
}
let Image$1 = class Image2 {
  constructor(element, config) {
    __publicField(this, "element");
    __publicField(this, "config");
    __publicField(this, "picture");
    __publicField(this, "observer");
    __publicField(this, "loader");
    __publicField(this, "size");
    this.element = element;
    this.config = config;
    this.picture = this.element.closest("picture");
    this.observer = new Observer(this.element);
    this.loader = new Loader();
    this.size = {
      width: this.element.offsetWidth,
      height: this.element.offsetHeight
    };
    this.init();
  }
  getRequestUri() {
    return `/-/img/${this.size.width}x${this.size.height}/${Math.round(window.innerWidth)}/${this.config}/`;
  }
  loadImage() {
    return this.loader.requestImage(this.getRequestUri()).then((config) => {
      Object.keys(config.attributes).forEach((key) => {
        key === "src" || this.element.setAttribute(key, config.attributes[key]);
      });
      this.element.style.aspectRatio = (config.aspectRatio[0] || config.attributes.width) + "/" + (config.aspectRatio[1] || config.attributes.height);
      return this.loader.preloadImage(config.attributes.src).then(() => {
        this.element.src = config.attributes.src;
        this.element.style.removeProperty("aspect-ratio");
        this.removePictureTag();
      });
    }).catch((error) => {
      console.error("Fehler beim Laden des Bildes:", error);
    });
  }
  removePictureTag() {
    var _a;
    if ((_a = this.picture) == null ? void 0 : _a.parentNode) {
      this.picture.parentNode.insertBefore(this.element, this.picture);
      this.picture.remove();
    }
  }
  observeElement() {
    this.observer.inView(() => this.loadImage().then(() => {
      this.observer.onResize((size) => {
        this.size = size;
        this.observeElement();
      }, this.size);
    }));
  }
  init() {
    ["data-config", "onload", "srcset"].forEach((attr) => {
      this.element.removeAttribute(attr);
    });
    this.observeElement();
  }
};
class Picturerino {
  static getConfig(element) {
    return element.getAttribute("data-config");
  }
  static handle(element) {
    const config = Picturerino.getConfig(element);
    new Image$1(element, config);
  }
}
window.Picturerino = {
  handle: (element) => Picturerino.handle(element)
};
