// Types & Interfaces
interface RatioConfig {
  width: number
  height: number
  isPortrait: boolean
}

type BreakpointData = Record<string, string>

// Constants
const PREDEFINED_RATIOS: [number, number][] = [
  [1, 1],   // square
  [16, 9],  // landscape/portrait 16:9
  [5, 4],   // landscape/portrait 5:4
  [4, 3],   // landscape/portrait 4:3
]

// Utility Functions
const parseRatio = (ratio: string): RatioConfig | null => {
  if (!ratio || ratio === '1:1') {
    return {width: 1, height: 1, isPortrait: false}
  }

  const [width, height] = ratio.split(':').map(Number)
  return {width, height, isPortrait: height > width}
}

const formatRatio = ({width, height, isPortrait}: RatioConfig): string => {
  if (width === 1 && height === 1) return '1:1'
  return isPortrait ? `${height}:${width}` : `${width}:${height}`
}

const parseBreakpoints = (input: string[] | string): string[] => {
  if (Array.isArray(input)) return input
  if (!input?.trim()) return []

  try {
    const parsed = JSON.parse(input)
    if (Array.isArray(parsed)) return parsed
    if (typeof parsed === 'object' && parsed !== null) return Object.values(parsed)
  } catch {
    return input.split(',').map(b => b.trim()).filter(Boolean)
  }

  return []
}

// Components
class RatioSelector {
  private readonly element: HTMLSelectElement
  private ratio: RatioConfig
  private readonly onChange: () => void

  constructor(initialRatio: string, onChange: () => void) {
    this.element = document.createElement('select')
    this.element.required = true
    this.element.className = 'form-select form-control'
    this.ratio = parseRatio(initialRatio) || {width: 0, height: 0, isPortrait: false}
    this.onChange = onChange

    this.initializeSelect()
    this.setValue(initialRatio)
  }

  private initializeSelect(): void {
    const options = this.generateOptions()
    this.element.innerHTML = options
      .map(opt => `<option value="${opt.value}">${opt.text}</option>`)
      .join('')

    this.element.addEventListener('change', () => {
      this.ratio = parseRatio(this.element.value) || this.ratio
      this.onChange()
    })
  }

  private generateOptions(): Array<{value: string, text: string}> {
    const defaultOption = {value: '', text: 'Select ratio'}
    const ratioOptions = PREDEFINED_RATIOS.map(([w, h]) => {
      const config = {
        width: w,
        height: h,
        isPortrait: this.ratio.isPortrait,
      }
      const value = formatRatio(config)
      return {value, text: value}
    })
    return [defaultOption, ...ratioOptions]
  }

  public getElement(): HTMLSelectElement {
    return this.element
  }

  public getValue(): string {
    return formatRatio(this.ratio)
  }

  public toggleOrientation(): void {
    if (this.ratio.width === 1 && this.ratio.height === 1) return

    const currentValue = this.getValue()
    this.ratio = {...this.ratio, isPortrait: !this.ratio.isPortrait}

    // Regenerate all options with new orientation
    const options = this.generateOptions()
    this.element.innerHTML = options
      .map(opt => `<option value="${opt.value}">${opt.text}</option>`)
      .join('')

    // Update value after regenerating options
    const newValue = formatRatio(this.ratio)
    this.setValue(newValue)

    if (currentValue !== newValue) {
      this.onChange()
    }
  }

  private setValue(value: string): void {
    this.element.value = value
  }
}

class BreakpointControl {
  private readonly element: HTMLDivElement
  private readonly breakpoint: string
  private readonly ratioSelector: RatioSelector

  constructor(
    breakpoint: string,
    initialRatio: string,
    order: number,
    onDelete: () => void,
    onRatioChange: () => void,
  ) {
    this.breakpoint = breakpoint
    this.ratioSelector = new RatioSelector(initialRatio, onRatioChange)
    this.element = this.createControl(order, onDelete)
  }

  private createControl(order: number, onDelete: () => void): HTMLDivElement {
    const container = document.createElement('div')
    container.className = 'aspectratio__breakpoint'
    container.dataset.breakpoint = this.breakpoint
    container.style.order = String(order)

    const template = `
      <span class="aspectratio__breakpoint-label">${this.breakpoint}</span>
      <span class="aspectratio__select"></span>
      <button type="button" class="aspectratio__breakpoint-remove btn btn-default">×</button>
      <button type="button" class="aspectratio__switch btn btn-default">⟷</button>
    `
    container.innerHTML = template

    container.querySelector('.aspectratio__select')?.appendChild(this.ratioSelector.getElement())
    container.querySelector('.aspectratio__breakpoint-remove')?.addEventListener('click', onDelete)
    container.querySelector('.aspectratio__switch')?.addEventListener('click', () => this.ratioSelector.toggleOrientation())

    return container
  }

  public getElement(): HTMLElement {
    return this.element
  }

  public getRatio(): string {
    return this.ratioSelector.getValue()
  }
}

class BreakpointManager {
  private readonly element: HTMLSelectElement
  private readonly availableBreakpoints: Set<string>
  private readonly usedBreakpoints: Set<string>

  constructor(breakpoints: string[]) {
    this.element = document.createElement('select')
    this.element.className = 'aspectratio__breakpoint-select form-select form-control'
    this.availableBreakpoints = new Set(breakpoints)
    this.usedBreakpoints = new Set<string>()
    this.updateOptions()
  }

  public getElement(): HTMLElement {
    return this.element
  }

  public markBreakpointAsUsed(breakpoint: string): void {
    if (this.availableBreakpoints.has(breakpoint)) {
      this.usedBreakpoints.add(breakpoint)
      this.updateOptions()
    }
  }

  public async waitForSelection(): Promise<string> {
    return new Promise(resolve => {
      const handler = (): void => {
        const value = this.element.value
        if (!value) return

        this.element.removeEventListener('change', handler)
        this.element.value = ''
        this.usedBreakpoints.add(value)
        this.updateOptions()
        resolve(value)
      }

      this.element.addEventListener('change', handler)
    })
  }

  public removeBreakpoint(breakpoint: string): void {
    this.usedBreakpoints.delete(breakpoint)
    this.updateOptions()
  }

  private updateOptions(): void {
    const available = Array.from(this.availableBreakpoints)
      .filter(bp => !this.usedBreakpoints.has(bp))

    this.element.innerHTML = `
      <option value="">Add aspect ratio</option>
      ${available.map(bp => `<option value="${bp}">${bp}</option>`).join('')}
    `

    this.element.disabled = available.length === 0
  }
}

export default class AspectRatio {
  private readonly wrapper: HTMLElement
  private readonly hiddenField: HTMLInputElement
  private readonly breakpointsList: HTMLElement
  private readonly breakpointManager: BreakpointManager
  private readonly breakpoints = new Map<string, BreakpointControl>()
  private readonly orderedBreakpoints: string[]

  constructor(fieldId: string, wrapperId: string, data: string, breakpoints: string[] | string) {
    const wrapperEl = document.getElementById(wrapperId)
    const fieldEl = document.getElementById(fieldId)

    if (!wrapperEl || !fieldEl) {
      throw new Error('Required elements not found')
    }

    this.wrapper = wrapperEl
    this.hiddenField = fieldEl as HTMLInputElement
    this.orderedBreakpoints = parseBreakpoints(breakpoints)
    this.breakpointManager = new BreakpointManager(this.orderedBreakpoints)
    this.breakpointsList = document.createElement('div')
    this.breakpointsList.className = 'aspectratio__breakpoint-list'

    this.initialize(data)
  }

  private initialize(data: string): void {
    this.wrapper.className = 'aspectratio'
    this.wrapper.append(this.breakpointManager.getElement(), this.breakpointsList)

    if (data?.trim() && data.startsWith('{')) {
      try {
        const savedData = JSON.parse(data) as BreakpointData
        Object.entries(savedData).forEach(([breakpoint, ratio]) => {
          this.addBreakpoint(breakpoint, ratio)
          this.breakpointManager.markBreakpointAsUsed(breakpoint)
        })
      } catch (error) {
        console.error('Failed to parse saved aspect ratios:', error)
      }
    }

    this.listenForBreakpoints()
  }

  private async listenForBreakpoints(): Promise<void> {
    while (true) {
      const breakpoint = await this.breakpointManager.waitForSelection()
      this.addBreakpoint(breakpoint)
    }
  }

  private addBreakpoint(value: string, ratio: string = ''): void {
    const order = this.orderedBreakpoints.indexOf(value)
    const control = new BreakpointControl(
      value,
      ratio,
      order,
      () => this.removeBreakpoint(value),
      () => this.updateHiddenField(),
    )

    this.breakpoints.set(value, control)
    this.breakpointsList.appendChild(control.getElement())
    this.updateHiddenField()
  }

  private removeBreakpoint(value: string): void {
    const control = this.breakpoints.get(value)
    if (control) {
      control.getElement().remove()
      this.breakpoints.delete(value)
      this.breakpointManager.removeBreakpoint(value)
      this.updateHiddenField()
    }
  }

  private updateHiddenField(): void {
    const ratios = Array.from(this.breakpoints.entries()).reduce((acc, [key, control]) => {
      acc[key] = control.getRatio()
      return acc
    }, {} as BreakpointData)

    this.hiddenField.value = JSON.stringify(ratios)
  }
}
