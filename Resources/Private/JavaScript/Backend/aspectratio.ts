type Ratio = [number, number]

class RatioSelector {
  private static readonly ratios: Ratio[] = [
    [1, 1],   // square
    [16, 9],  // landscape/portrait 16:9
    [5, 4],   // landscape/portrait 5:4
    [4, 3],   // landscape/portrait 4:3
  ]

  private select: HTMLSelectElement
  private switchButton: HTMLButtonElement
  private onChange: () => void
  private isPortrait: boolean = false

  constructor(initialValue: string, onChange: () => void) {
    this.onChange = onChange
    this.isPortrait = initialValue ? this.isRatioPortrait(initialValue) : false
    this.select = this.createSelect(initialValue)
    this.switchButton = this.createSwitchButton()
  }

  private createOptions(): void {
    // Entferne alle existierenden Optionen
    while (this.select.firstChild) {
      this.select.removeChild(this.select.firstChild)
    }

    // Füge die Default-Option hinzu
    const defaultOption = document.createElement('option')
    defaultOption.value = ''
    defaultOption.textContent = 'Select ratio'
    this.select.appendChild(defaultOption)

    // Füge die Ratio-Optionen hinzu
    RatioSelector.ratios.forEach(([width, height]) => {
      const option = document.createElement('option')
      const [first, second] = this.isPortrait ? [height, width] : [width, height]
      const value = `${first}:${second}`
      option.value = value
      option.textContent = value
      this.select.appendChild(option)
    })
  }

  private createSelect(initialValue: string): HTMLSelectElement {
    const select = document.createElement('select')
    select.className = 'ratio-select'
    select.required = true
    this.select = select

    this.createOptions()

    if (initialValue) {
      select.value = initialValue
    }

    select.addEventListener('change', () => this.onChange())
    return select
  }

  private createSwitchButton(): HTMLButtonElement {
    const button = document.createElement('button')
    button.textContent = '⟷'
    button.className = 'switch-ratio'
    button.type = 'button'
    button.addEventListener('click', () => this.switchRatio())
    return button
  }

  private isRatioPortrait(ratio: string): boolean {
    if (ratio === '1:1') return false
    const [width, height] = ratio.split(':').map(Number)
    return height > width
  }

  private recreateSelect(selectedIndex: number): void {
    this.createOptions()
    this.select.selectedIndex = selectedIndex
  }

  private switchRatio(): void {
    const currentRatio = this.select.value
    if (!currentRatio || currentRatio === '1:1') return

    const selectedIndex = this.select.selectedIndex
    this.isPortrait = !this.isPortrait
    this.recreateSelect(selectedIndex)

    // Manuell ein Change-Event auslösen
    const event = new Event('change', {bubbles: true})
    this.select.dispatchEvent(event)
  }

  public getElement(): HTMLElement {
    const container = document.createElement('div')
    container.className = 'ratio-control'
    container.appendChild(this.select)
    container.appendChild(this.switchButton)
    return container
  }

  public getValue(): string {
    return this.select.value
  }
}

class Breakpoint {
  private element: HTMLElement
  private ratioSelector: RatioSelector
  private value: string
  private order: number

  constructor(
    value: string,
    initialRatio: string,
    order: number,
    onDelete: () => void,
    onRatioChange: () => void,
  ) {
    this.value = value
    this.order = order
    this.ratioSelector = new RatioSelector(initialRatio, onRatioChange)
    this.element = this.createContainer(onDelete)
  }

  private createContainer(onDelete: () => void): HTMLElement {
    const container = document.createElement('div')
    container.className = 'breakpoint-container'
    container.setAttribute('data-breakpoint', this.value)
    container.style.order = String(this.order)

    const header = this.createHeader(onDelete)
    container.appendChild(header)
    container.appendChild(this.ratioSelector.getElement())

    return container
  }

  private createHeader(onDelete: () => void): HTMLElement {
    const header = document.createElement('div')
    header.className = 'breakpoint-header'

    const title = document.createElement('h3')
    title.textContent = this.value

    const removeButton = document.createElement('button')
    removeButton.textContent = '×'
    removeButton.className = 'remove-breakpoint'
    removeButton.type = 'button'
    removeButton.addEventListener('click', onDelete)

    header.appendChild(title)
    header.appendChild(removeButton)
    return header
  }

  public getElement(): HTMLElement {
    return this.element
  }

  public getValue(): string {
    return this.value
  }

  public getRatio(): string {
    return this.ratioSelector.getValue()
  }
}

class BreakpointSelector {
  private select: HTMLSelectElement
  private breakpoints: string[]
  private usedBreakpoints: Set<string>
  private onSelect?: (value: string) => void

  constructor(breakpoints: string[], onSelect?: (value: string) => void) {
    this.breakpoints = breakpoints
    this.usedBreakpoints = new Set<string>()
    this.select = document.createElement('select')
    this.onSelect = onSelect
    this.select.className = 'breakpoint-select'
    this.initializeSelect()
  }

  private initializeSelect(): void {
    const defaultOption = document.createElement('option')
    defaultOption.value = ''
    defaultOption.textContent = 'Select breakpoint'
    this.select.appendChild(defaultOption)
    this.updateOptions()
  }

  public getElement(): HTMLElement {
    return this.select
  }

  public addBreakpoint(): Promise<string> {
    return new Promise<string>(resolve => {
      const handleChange = (event: Event): void => {
        const target = event.target as HTMLSelectElement
        const value = target.value
        if (value) {
          this.select.removeEventListener('change', handleChange)
          this.select.value = ''
          this.usedBreakpoints.add(value)
          this.updateOptions()
          if (this.onSelect) {
            this.onSelect(value)
          }
          resolve(value)
        }
      }

      this.select.addEventListener('change', handleChange)
    })
  }

  public removeBreakpoint(breakpoint: string): void {
    this.usedBreakpoints.delete(breakpoint)
    this.updateOptions()
  }

  private updateOptions(): void {
    while (this.select.options.length > 1) {
      this.select.remove(1)
    }

    const availableBreakpoints = this.breakpoints.filter(bp => !this.usedBreakpoints.has(bp))
    availableBreakpoints.forEach(breakpoint => {
      const option = document.createElement('option')
      option.value = breakpoint
      option.textContent = breakpoint
      this.select.appendChild(option)
    })

    this.select.disabled = availableBreakpoints.length === 0
  }
}

export default class AspectRatio {
  private wrapper: HTMLElement | null = null
  private hiddenField: HTMLInputElement | null = null
  private breakpointSelector: BreakpointSelector
  private breakpointsList: HTMLElement
  private breakpoints: Map<string, Breakpoint> = new Map()
  private breakpointValues: string[]

  constructor(fieldId: string, wrapperId: string, data: string, breakpointValues: string[] | string) {
    this.wrapper = document.getElementById(wrapperId)
    this.hiddenField = document.getElementById(fieldId) as HTMLInputElement

    if (!this.wrapper) {
      throw new Error('Wrapper element not found')
    }

    this.breakpointValues = this.parseBreakpoints(breakpointValues)
    this.breakpointSelector = new BreakpointSelector(
      this.breakpointValues,
      (value: string) => this.addBreakpoint(value),
    )
    this.breakpointsList = this.createBreakpointsList()
    this.initialize(data)
    this.setupBreakpointListener()
  }

  private parseBreakpoints(breakpoints: string[] | string): string[] {
    if (Array.isArray(breakpoints)) {
      return breakpoints
    }

    if (typeof breakpoints === 'string') {
      if (!breakpoints.trim()) {
        return []
      }

      try {
        const parsed = JSON.parse(breakpoints)
        if (Array.isArray(parsed)) {
          return parsed
        }
        if (typeof parsed === 'object' && parsed !== null) {
          return Object.values(parsed)
        }
      } catch {
        return breakpoints.split(',').map(b => b.trim()).filter(Boolean)
      }
    }

    return []
  }

  private initialize(data: string): void {
    this.wrapper?.appendChild(this.breakpointSelector.getElement())
    this.wrapper?.appendChild(this.breakpointsList)

    if (!data?.trim()) return

    try {
      if (!data.trim().startsWith('{')) return

      const savedData = JSON.parse(data)
      Object.entries(savedData).forEach(([breakpoint, ratio]) => {
        this.addBreakpoint(breakpoint, ratio as string)
      })
    } catch (error) {
      console.error('Failed to parse saved aspect ratios:', error)
    }
  }

  private setupBreakpointListener(): void {
    const listenForBreakpoint = async (): Promise<void> => {
      await this.breakpointSelector.addBreakpoint()
      listenForBreakpoint()
    }

    listenForBreakpoint()
  }

  private createBreakpointsList(): HTMLElement {
    const container = document.createElement('div')
    container.className = 'breakpoints-list'
    container.style.display = 'flex'
    container.style.flexDirection = 'column'
    return container
  }

  private addBreakpoint(value: string, ratio: string = ''): void {
    const order = this.breakpointValues.indexOf(value)
    const breakpoint = new Breakpoint(
      value,
      ratio,
      order,
      () => this.removeBreakpoint(value),
      () => this.updateHiddenField(),
    )

    this.breakpoints.set(value, breakpoint)
    this.breakpointsList.appendChild(breakpoint.getElement())
    this.updateHiddenField()
  }

  private removeBreakpoint(value: string): void {
    const breakpoint = this.breakpoints.get(value)
    if (breakpoint) {
      breakpoint.getElement().remove()
      this.breakpoints.delete(value)
      this.breakpointSelector.removeBreakpoint(value)
      this.updateHiddenField()
    }
  }

  private updateHiddenField(): void {
    if (!this.hiddenField) return

    const ratios: Record<string, string> = {}
    this.breakpoints.forEach((breakpoint, key) => {
      ratios[key] = breakpoint.getRatio()
    })

    this.hiddenField.value = JSON.stringify(ratios)
  }
}
