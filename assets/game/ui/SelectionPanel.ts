import type { SelectionData } from 'tionManager.ts';

/**
 * SelectionPanel handles the UI display for selected objects
 * Shows information about the currently selected object in the bottom-left corner
 */
export class SelectionPanel {
  private element!: HTMLElement;
  private isVisible: boolean = false;

  constructor() {
    this.createElement();
  }

  /**
   * Create the DOM element for the selection panel
   */
  private createElement(): void {
    this.element = document.createElement('div');
    this.element.id = 'selection-panel';
    this.element.className = 'selection-panel hidden';
    
    this.element.innerHTML = `
      <div class="selection-header">
        <span class="selection-title">Zaznaczenie</span>
        <button class="selection-close" type="button">&times;</button>
      </div>
      <div class="selection-content">
        <div class="selection-info">
          <div class="object-type"></div>
          <div class="object-name"></div>
          <div class="object-position"></div>
          <div class="object-details"></div>
        </div>
      </div>
    `;

    // Add close button functionality
    const closeButton = this.element.querySelector('.selection-close') as HTMLElement;
    closeButton.addEventListener('click', () => {
      this.hide();
      // Emit custom event to clear selection
      this.element.dispatchEvent(new CustomEvent('clearSelection'));
    });

    document.body.appendChild(this.element);
  }

  /**
   * Handle selection change events
   */
  onSelectionChange(data: SelectionData | null): void {
    if (data) {
      this.showObject(data);
    } else {
      this.hide();
    }
  }

  /**
   * Show information about selected object
   */
  private showObject(data: SelectionData): void {
    const typeElement = this.element.querySelector('.object-type') as HTMLElement;
    const nameElement = this.element.querySelector('.object-name') as HTMLElement;
    const positionElement = this.element.querySelector('.object-position') as HTMLElement;
    const detailsElement = this.element.querySelector('.object-details') as HTMLElement;

    if (!typeElement || !nameElement || !positionElement || !detailsElement) {
      return;
    }

    // Set basic information
    typeElement.textContent = this.getTypeDisplayName(data.type);
    nameElement.textContent = data.displayName;
    positionElement.textContent = `Pozycja: (${data.position.row}, ${data.position.col})`;

    // Display detailed information
    detailsElement.innerHTML = this.formatObjectDetails(data.type, data.info);

    this.show();
  }

  /**
   * Get display name for object type
   */
  private getTypeDisplayName(type: string): string {
    const typeNames: Record<string, string> = {
      'hex': 'Pole',
      'player': 'Gracz',
      'building': 'Budynek',
      'unit': 'Jednostka'
    };
    return typeNames[type] || type;
  }

  /**
   * Format object details based on type
   */
  private formatObjectDetails(type: string, info: any): string {
    switch (type) {
      case 'hex':
        return this.formatHexDetails(info);
      case 'player':
        return this.formatPlayerDetails(info);
      case 'building':
        return this.formatBuildingDetails(info);
      case 'unit':
        return this.formatUnitDetails(info);
      default:
        return '';
    }
  }

  /**
   * Format hex tile details
   */
  private formatHexDetails(info: any): string {
    return `
      <div class="detail-row">
        <span class="detail-label">Teren:</span>
        <span class="detail-value">${info.terrainName || 'Nieznany'}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Koszt ruchu:</span>
        <span class="detail-value">${info.movementCost || 0}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Obrona:</span>
        <span class="detail-value">${info.defense || 0}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Zasoby:</span>
        <span class="detail-value">${info.resources || 0}</span>
      </div>
    `;
  }

  /**
   * Format player details
   */
  private formatPlayerDetails(info: any): string {
    return `
      <div class="detail-row">
        <span class="detail-label">Punkty ruchu:</span>
        <span class="detail-value">${info.movementPoints || 0}/${info.maxMovementPoints || 0}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Kolor:</span>
        <span class="detail-value" style="color: #${info.color?.toString(16) || '000000'}">#${info.color?.toString(16) || '000000'}</span>
      </div>
    `;
  }

  /**
   * Format building details (for future use)
   */
  private formatBuildingDetails(info: any): string {
    return `
      <div class="detail-row">
        <span class="detail-label">Typ:</span>
        <span class="detail-value">${info.buildingType || 'Nieznany'}</span>
      </div>
    `;
  }

  /**
   * Format unit details (for future use)
   */
  private formatUnitDetails(info: any): string {
    return `
      <div class="detail-row">
        <span class="detail-label">HP:</span>
        <span class="detail-value">${info.health || 0}/${info.maxHealth || 0}</span>
      </div>
    `;
  }

  /**
   * Show the selection panel
   */
  private show(): void {
    if (!this.isVisible) {
      this.element.classList.remove('hidden');
      this.element.classList.add('visible');
      this.isVisible = true;
    }
  }

  /**
   * Hide the selection panel
   */
  private hide(): void {
    if (this.isVisible) {
      this.element.classList.remove('visible');
      this.element.classList.add('hidden');
      this.isVisible = false;
    }
  }

  /**
   * Get the DOM element
   */
  getElement(): HTMLElement {
    return this.element;
  }

  /**
   * Destroy the selection panel
   */
  destroy(): void {
    if (this.element.parentNode) {
      this.element.parentNode.removeChild(this.element);
    }
  }
} 