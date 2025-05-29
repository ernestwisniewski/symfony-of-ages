import type { SelectionData } from './SelectionManager.ts';

/**
 * SelectionPanel displays information about selected objects
 * 
 * Shows selection info in bottom-left corner with Polish labels.
 * Supports different object types (hex/player/building/unit).
 */
export class SelectionPanel {
  private element: HTMLElement;
  private isVisible: boolean = false;

  constructor() {
    this.element = this.createElement();
    this.attachToDOM();
  }

  /**
   * Handles selection change events from SelectionManager
   * @param data - Selection data or null if nothing selected
   */
  onSelectionChange(data: SelectionData | null): void {
    if (data) {
      this.showSelection(data);
    } else {
      this.hide();
    }
  }

  /**
   * Shows selection information
   * @param data - Selection data to display
   */
  private showSelection(data: SelectionData): void {
    this.updateContent(data);
    this.show();
  }

  /**
   * Updates panel content with selection data
   * @param data - Selection data to display
   */
  private updateContent(data: SelectionData): void {
    const typeLabel = this.getTypeLabel(data.type);
    const positionText = `(${data.position.row}, ${data.position.col})`;
    
    let infoHtml = '';
    if (data.info && Object.keys(data.info).length > 0) {
      infoHtml = Object.entries(data.info)
        .map(([key, value]) => `<div class="selection-info-item"><strong>${key}:</strong> ${value}</div>`)
        .join('');
    }

    this.element.innerHTML = `
      <div class="selection-header">
        <div class="selection-title">${data.displayName}</div>
        <div class="selection-type">${typeLabel}</div>
        <div class="selection-position">${positionText}</div>
        <button class="selection-close" title="Zamknij">×</button>
      </div>
      ${infoHtml ? `<div class="selection-info">${infoHtml}</div>` : ''}
    `;

    // Setup close button
    const closeButton = this.element.querySelector('.selection-close');
    if (closeButton) {
      closeButton.addEventListener('click', () => {
        this.dispatchClearEvent();
      });
    }
  }
} 