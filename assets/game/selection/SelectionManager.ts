import type {SelectableObject, SelectionData} from '../core/types';

/**
 * SelectionManager handles object selection state
 * Manages single object selection and notifies listeners of changes
 */
export class SelectionManager {
  private selectedObject: SelectableObject | null = null;
  private changeCallback: ((data: SelectionData | null) => void) | null = null;

  /**
   * Set callback for selection changes
   */
  onSelectionChange(callback: (data: SelectionData | null) => void): void {
    this.changeCallback = callback;
  }

  /**
   * Select an object
   */
  select(object: SelectableObject): void {
    // If clicking the same object, deselect it
    if (this.selectedObject && this.selectedObject.id === object.id) {
      this.clearSelection();
      return;
    }

    this.selectedObject = object;
    this.notifyChange();
  }

  /**
   * Clear selection
   */
  clearSelection(): void {
    this.selectedObject = null;
    this.notifyChange();
  }

  /**
   * Get currently selected object
   */
  getSelected(): SelectableObject | null {
    return this.selectedObject;
  }

  /**
   * Check if an object is selected
   */
  isSelected(objectId: string): boolean {
    return this.selectedObject?.id === objectId;
  }

  /**
   * Notify listeners of selection change
   */
  private notifyChange(): void {
    if (this.changeCallback) {
      const data = this.selectedObject ? {
        id: this.selectedObject.id,
        type: this.selectedObject.type,
        position: this.selectedObject.position,
        displayName: this.selectedObject.displayName,
        info: this.selectedObject.getSelectionInfo()
      } : null;

      this.changeCallback(data);
    }
  }
}
