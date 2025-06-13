import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
  connect(): void {
    console.log('SelectionPanel controller connected')
    
    // Listen for Live Component events on document
    document.addEventListener('hexSelected', (event: any) => {
      console.log('Hex selected event received:', event.detail)
      this.handleHexSelection(event.detail)
    })
    
    document.addEventListener('unitSelected', (event: any) => {
      console.log('Unit selected event received:', event.detail)
      this.handleUnitSelection(event.detail)
    })
  }

  /**
   * Handle hex selection from Live Component event
   */
  private handleHexSelection(hexData: any): void {
    console.log('Handling hex selection in Stimulus controller:', hexData)
    
    // Use Live Component action system
    const actionEvent = new CustomEvent('live#action', {
      detail: {
        action: 'selectHex',
        args: [hexData]
      }
    })
    
    this.element.dispatchEvent(actionEvent)
  }

  /**
   * Handle unit selection from Live Component event
   */
  private handleUnitSelection(unitData: any): void {
    console.log('Handling unit selection in Stimulus controller:', unitData)
    
    // Use Live Component action system
    const actionEvent = new CustomEvent('live#action', {
      detail: {
        action: 'selectUnit',
        args: [unitData]
      }
    })
    
    this.element.dispatchEvent(actionEvent)
  }
} 