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
      const entry = entries[0];
      this.resizeTimeout = window.setTimeout(() => {
        var _a2;
        if (size && size.width === entry.contentRect.width && size.height === entry.contentRect.height) {
          return;
        }
        console.log("ResizeObserver", entry.contentRect.width, entry.contentRect.height);
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
  requestImage(url) {
    if (this.cache.has(url)) {
      return Promise.resolve(this.cache.get(url));
    }
    return fetch(url).then(async (response) => {
      const data = await response.json();
      if (!response.ok) {
        if (data.error) {
          return Promise.reject(data.error);
        } else {
          return Promise.reject({
            error: {
              message: response.statusText,
              code: response.status
            }
          });
        }
      }
      return data;
    }).then((config) => {
      this.cache.set(url, config);
      return config;
    });
  }
  clearCache() {
    this.cache.clear();
  }
  removeFromCache(url) {
    this.cache.delete(url);
  }
}
class Image {
  constructor(element, config) {
    __publicField(this, "element");
    __publicField(this, "config");
    __publicField(this, "observer");
    __publicField(this, "loader");
    __publicField(this, "sources");
    __publicField(this, "size");
    this.element = element;
    this.config = config;
    this.observer = new Observer(this.element);
    this.loader = new Loader();
    this.sources = {};
    this.size = {
      width: this.element.offsetWidth,
      height: this.element.offsetHeight
    };
    this.observeElement = this.observeElement.bind(this);
    this.init();
  }
  getRequestUri() {
    return `/-/img/${this.size.width}x${this.size.height}/${Math.round(window.innerWidth)}/${this.config}/`;
  }
  updateImage(imageResponse) {
    this.element.width = imageResponse.processed.width;
    this.element.height = imageResponse.processed.height;
    this.element.src = imageResponse.processed.img;
    if (imageResponse.processed.img2x) {
      this.element.srcset = imageResponse.processed.img2x + " 2x";
    }
  }
  updateSourceTag(view, imageResponse) {
    const source = this.sources[view];
    if (source) {
      source.width = imageResponse.processed.width;
      source.height = imageResponse.processed.height;
      if (imageResponse.processed.img2x) {
        source.srcset = imageResponse.processed.img + "," + imageResponse.processed.img2x + " 2x";
      } else {
        source.srcset = imageResponse.processed.img;
      }
    }
  }
  getSourceKey(view) {
    const views = Object.keys(this.sources).map(Number);
    if (views.length) {
      const lowerViews = views.filter((value) => value <= view);
      return lowerViews.length ? Math.max(...lowerViews) : 0;
    }
    return 0;
  }
  updateSource() {
    this.loader.requestImage(this.getRequestUri()).then((result) => {
      const sourceKey = this.getSourceKey(result.view);
      sourceKey ? this.updateSourceTag(sourceKey, result) : this.updateImage(result);
      this.element.addEventListener("load", this.observeElement, { once: true });
    }).catch((error) => {
      if (error.code === 1745092982) {
        this.observeElement();
      } else {
        console.warn(error);
      }
    });
  }
  observeElement() {
    this.observer.onResize((size) => {
      this.size = size;
      this.updateSource();
    }, this.size);
  }
  init() {
    ["data-config", "onload", "srcset"].forEach((attr) => {
      this.element.removeAttribute(attr);
    });
    const picture = this.element.closest("picture");
    if (picture) {
      Array.prototype.slice.call(picture.getElementsByTagName("source")).forEach((source) => {
        const view = parseInt(source.getAttribute("media").match(/\d+/)[0], 10);
        view && (this.sources[view] = source);
      });
    }
    this.observer.inView(() => this.updateSource());
  }
}
class Picturerino {
  static getConfig(element) {
    return element.getAttribute("data-config");
  }
  static handle(element) {
    const config = Picturerino.getConfig(element);
    new Image(element, config);
  }
}
window.Picturerino = {
  handle: (element) => Picturerino.handle(element)
};
