var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);
import Icons from "@typo3/backend/icons.js";
const PREDEFINED_RATIOS = [
  [1, 1],
  [5, 4],
  [4, 3],
  [16, 9],
  [21, 10]
];
const parseRatio = (ratio) => {
  if (!ratio || ratio === "1:1") {
    return { width: 1, height: 1, isPortrait: false };
  }
  const [width, height] = ratio.split(":").map(Number);
  return { width, height, isPortrait: height > width };
};
const formatRatio = ({ width, height, isPortrait }) => {
  if (width === 1 && height === 1) return "1:1";
  return isPortrait ? `${height}:${width}` : `${width}:${height}`;
};
const parseBreakpoints = (input) => {
  if (Array.isArray(input)) return input;
  if (!(input == null ? void 0 : input.trim())) return [];
  try {
    const parsed = JSON.parse(input);
    if (Array.isArray(parsed)) return parsed;
    if (typeof parsed === "object" && parsed !== null) return Object.values(parsed);
  } catch {
    return input.split(",").map((b) => b.trim()).filter(Boolean);
  }
  return [];
};
class RatioSelector {
  constructor(initialRatio, onChange) {
    __publicField(this, "element");
    __publicField(this, "ratio");
    __publicField(this, "onChange");
    this.element = document.createElement("select");
    this.element.required = true;
    this.element.className = "form-select form-control";
    this.ratio = parseRatio(initialRatio) || { width: 0, height: 0, isPortrait: false };
    this.onChange = onChange;
    this.initializeSelect();
    this.setValue(initialRatio);
  }
  initializeSelect() {
    const options = this.generateOptions();
    this.element.innerHTML = options.map((opt) => `<option value="${opt.value}">${opt.text}</option>`).join("");
    this.element.addEventListener("change", () => {
      this.ratio = parseRatio(this.element.value) || this.ratio;
      this.onChange();
    });
  }
  generateOptions() {
    const defaultOption = { value: "", text: "Select ratio" };
    const ratioOptions = PREDEFINED_RATIOS.map(([w, h]) => {
      const config = {
        width: w,
        height: h,
        isPortrait: this.ratio.isPortrait
      };
      const value = formatRatio(config);
      return { value, text: value };
    });
    return [defaultOption, ...ratioOptions];
  }
  getElement() {
    return this.element;
  }
  getValue() {
    return formatRatio(this.ratio);
  }
  toggleOrientation() {
    if (this.ratio.width === 1 && this.ratio.height === 1) return;
    const currentValue = this.getValue();
    this.ratio = { ...this.ratio, isPortrait: !this.ratio.isPortrait };
    const options = this.generateOptions();
    this.element.innerHTML = options.map((opt) => `<option value="${opt.value}">${opt.text}</option>`).join("");
    const newValue = formatRatio(this.ratio);
    this.setValue(newValue);
    if (currentValue !== newValue) {
      this.onChange();
    }
  }
  setValue(value) {
    this.element.value = value;
  }
}
class BreakpointControl {
  constructor(breakpoint, initialRatio, order, onDelete, onRatioChange) {
    __publicField(this, "element");
    __publicField(this, "breakpoint");
    __publicField(this, "ratioSelector");
    this.breakpoint = breakpoint;
    this.ratioSelector = new RatioSelector(initialRatio, onRatioChange);
    this.element = this.createControl(order, onDelete);
  }
  createControl(order, onDelete) {
    const container = document.createElement("div");
    container.className = "aspectratio__breakpoint";
    container.dataset.breakpoint = this.breakpoint;
    container.style.order = String(order);
    Icons.getIcon("actions-delete", Icons.sizes.small).then((deleteIcon) => {
      Icons.getIcon("actions-exchange", Icons.sizes.small).then((switchIcon) => {
        var _a, _b, _c;
        const template = `
          <span class="aspectratio__breakpoint-label">${this.breakpoint}</span>
          <span class="aspectratio__select"></span>
          <button type="button" class="aspectratio__breakpoint-remove btn btn-default">${deleteIcon}</button>
          <button type="button" class="aspectratio__switch btn btn-default">${switchIcon}</button>
        `;
        container.innerHTML = template;
        (_a = container.querySelector(".aspectratio__select")) == null ? void 0 : _a.appendChild(this.ratioSelector.getElement());
        (_b = container.querySelector(".aspectratio__breakpoint-remove")) == null ? void 0 : _b.addEventListener("click", onDelete);
        (_c = container.querySelector(".aspectratio__switch")) == null ? void 0 : _c.addEventListener("click", () => this.ratioSelector.toggleOrientation());
      });
    });
    return container;
  }
  getElement() {
    return this.element;
  }
  getRatio() {
    return this.ratioSelector.getValue();
  }
}
class BreakpointManager {
  constructor(breakpoints) {
    __publicField(this, "element");
    __publicField(this, "availableBreakpoints");
    __publicField(this, "usedBreakpoints");
    this.element = document.createElement("select");
    this.element.className = "aspectratio__breakpoint-select form-select form-control";
    this.availableBreakpoints = new Set(breakpoints);
    this.usedBreakpoints = /* @__PURE__ */ new Set();
    this.updateOptions();
  }
  getElement() {
    return this.element;
  }
  markBreakpointAsUsed(breakpoint) {
    if (this.availableBreakpoints.has(breakpoint)) {
      this.usedBreakpoints.add(breakpoint);
      this.updateOptions();
    }
  }
  async waitForSelection() {
    return new Promise((resolve) => {
      const handler = () => {
        const value = this.element.value;
        if (!value) return;
        this.element.removeEventListener("change", handler);
        this.element.value = "";
        this.usedBreakpoints.add(value);
        this.updateOptions();
        resolve(value);
      };
      this.element.addEventListener("change", handler);
    });
  }
  removeBreakpoint(breakpoint) {
    this.usedBreakpoints.delete(breakpoint);
    this.updateOptions();
  }
  updateOptions() {
    const available = Array.from(this.availableBreakpoints).filter((bp) => !this.usedBreakpoints.has(bp));
    this.element.innerHTML = `
      <option value="">Add aspect ratio</option>
      ${available.map((bp) => `<option value="${bp}">${bp}</option>`).join("")}
    `;
    this.element.disabled = available.length === 0;
  }
}
class AspectRatio {
  constructor(fieldId, wrapperId, data, breakpoints) {
    __publicField(this, "wrapper");
    __publicField(this, "hiddenField");
    __publicField(this, "breakpointsList");
    __publicField(this, "breakpointManager");
    __publicField(this, "breakpoints", /* @__PURE__ */ new Map());
    __publicField(this, "orderedBreakpoints");
    const wrapperEl = document.getElementById(wrapperId);
    const fieldEl = document.getElementById(fieldId);
    if (!wrapperEl || !fieldEl) {
      throw new Error("Required elements not found");
    }
    this.wrapper = wrapperEl;
    this.hiddenField = fieldEl;
    this.orderedBreakpoints = parseBreakpoints(breakpoints);
    this.breakpointManager = new BreakpointManager(this.orderedBreakpoints);
    this.breakpointsList = document.createElement("div");
    this.breakpointsList.className = "aspectratio__breakpoint-list";
    this.initialize(data);
  }
  initialize(data) {
    this.wrapper.className = "aspectratio";
    this.wrapper.append(this.breakpointManager.getElement(), this.breakpointsList);
    if ((data == null ? void 0 : data.trim()) && data.startsWith("{")) {
      try {
        const savedData = JSON.parse(data);
        Object.entries(savedData).forEach(([breakpoint, ratio]) => {
          this.addBreakpoint(breakpoint, ratio);
          this.breakpointManager.markBreakpointAsUsed(breakpoint);
        });
      } catch (error) {
        console.error("Failed to parse saved aspect ratios:", error);
      }
    }
    this.listenForBreakpoints();
  }
  async listenForBreakpoints() {
    while (true) {
      const breakpoint = await this.breakpointManager.waitForSelection();
      this.addBreakpoint(breakpoint);
    }
  }
  addBreakpoint(value, ratio = "") {
    const order = this.orderedBreakpoints.indexOf(value);
    const control = new BreakpointControl(
      value,
      ratio,
      order,
      () => this.removeBreakpoint(value),
      () => this.updateHiddenField()
    );
    this.breakpoints.set(value, control);
    this.breakpointsList.appendChild(control.getElement());
    this.updateHiddenField();
  }
  removeBreakpoint(value) {
    const control = this.breakpoints.get(value);
    if (control) {
      control.getElement().remove();
      this.breakpoints.delete(value);
      this.breakpointManager.removeBreakpoint(value);
      this.updateHiddenField();
    }
  }
  updateHiddenField() {
    const ratios = Array.from(this.breakpoints.entries()).reduce((acc, [key, control]) => {
      acc[key] = control.getRatio();
      return acc;
    }, {});
    this.hiddenField.value = JSON.stringify(ratios);
  }
}
export {
  AspectRatio as default
};
