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
        if (size && Math.abs(size.width - entry.contentRect.width) / size.width <= 0.02 && Math.abs(size.height - entry.contentRect.height) / size.height <= 0.02) {
          return;
        }
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
  requestImage(url) {
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
    });
  }
}
class Image {
  constructor(element, config, wrap) {
    __publicField(this, "element");
    __publicField(this, "config");
    __publicField(this, "wrap");
    __publicField(this, "observer");
    __publicField(this, "loader");
    __publicField(this, "sources");
    __publicField(this, "webpSupport");
    __publicField(this, "size");
    this.element = element;
    this.config = config;
    this.wrap = wrap;
    this.observer = new Observer(this.element);
    this.loader = new Loader();
    this.sources = {};
    this.webpSupport = false;
    this.size = {
      width: this.element.offsetWidth,
      height: this.element.offsetHeight
    };
    this.observeElement = this.observeElement.bind(this);
    this.init();
  }
  checkWebpSupport() {
    const source = "data:image/webp;base64,UklGRkoAAABXRUJQVlA4WAoAAAAQAAAAAAAAAAAAQUxQSAwAAAARBxAR/Q9ERP8DAABWUDggGAAAABQBAJ0BKgEAAQAAAP4AAA3AAP7mtQAAAA==";
    const img = document.createElement("img");
    img.onload = () => {
      this.webpSupport = img.width > 0 && img.height > 0;
    };
    img.src = source;
  }
  getRequestUri() {
    const webp = this.webpSupport ? "webp/" : "";
    const width = parseInt(this.size.width.toString(), 10);
    const height = parseInt(this.size.height.toString(), 10);
    const view = Math.round(window.innerWidth);
    const retina = window.devicePixelRatio > 1 ? 2 : 1;
    return `/-/pictureino/img/${view}${retina}x${this.config}/${webp}${width}x${height}/`;
  }
  updateImage(imageResponse) {
    this.element.width = imageResponse.processed.width;
    this.element.height = imageResponse.processed.height;
    if (imageResponse.processed.img1x) {
      this.element.src = imageResponse.processed.img1x;
    }
    if (imageResponse.processed.img2x) {
      this.element.src = imageResponse.processed.img2x;
      this.element.srcset = imageResponse.processed.img2x + " 2x";
    } else {
      this.element.removeAttribute("srcset");
    }
  }
  updateSourceTag(view, imageResponse) {
    const source = this.sources[view];
    if (source) {
      source.width = imageResponse.processed.width;
      source.height = imageResponse.processed.height;
      if (imageResponse.processed.img1x) {
        source.srcset = imageResponse.processed.img1x;
      }
      if (imageResponse.processed.img2x) {
        source.srcset = imageResponse.processed.img2x + " 2x";
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
    const loaded = () => {
      this.wrap.dataset.loaded = "true";
      setTimeout(this.observeElement, 1e3);
    };
    if (this.size.width <= 50 || this.size.height <= 0) {
      return loaded();
    }
    this.wrap.dataset.loaded = "false";
    this.loader.requestImage(this.getRequestUri()).then((result) => {
      const sourceKey = this.getSourceKey(result.view);
      sourceKey ? this.updateSourceTag(sourceKey, result) : this.updateImage(result);
      this.element.addEventListener("load", loaded, { once: true });
    }).catch(() => loaded);
  }
  observeElement() {
    this.observer.onResize((size) => {
      this.size = size;
      this.updateSource();
    }, this.size);
  }
  init() {
    this.checkWebpSupport();
    ["onload", "srcset"].forEach((attr) => {
      this.element.removeAttribute(attr);
    });
    const picture = this.element.closest("picture");
    if (picture) {
      Array.prototype.slice.call(picture.getElementsByTagName("source")).forEach((source) => {
        const mediaAttr = source.getAttribute("media");
        if (mediaAttr) {
          const matches = mediaAttr.match(/\d+/);
          if (matches && matches[0]) {
            const view = parseInt(matches[0], 10);
            if (view) {
              this.sources[view] = source;
            }
          }
        }
      });
    }
    this.observer.inView(() => this.updateSource());
  }
}
class PictureinoWrap extends HTMLElement {
  constructor() {
    super();
  }
  connectedCallback() {
    const images = this.getElementsByTagName("img");
    const config = this.getAttribute("data-config") || "";
    if (images.length === 1 && config) {
      new Image(images[0], config, this);
    }
  }
}
customElements.define("pictureino-wrap", PictureinoWrap);
