/**
 * Interface for objects that can be selected on the map
 */
export interface SelectableObject {
  readonly id: string;
  readonly type: string;
  readonly position: { row: number; col: number };
  readonly displayName: string;
  getSelectionInfo(): Record<string, any>;
}

/**
 * Interface for selection data passed to callbacks
 */
export interface SelectionData {
  type: string;
  displayName: string;
  position: { row: number; col: number };
  info: Record<string, any>;
}

/**
 * SelectionManager handles the selection of map objects
 * 
 * Manages single object selection with selection change events.
 * Supports selecting different types of objects (hex tiles, players, buildings, units).
 */
export class SelectionManager {
  private selectedObject: SelectableObject | null = null;
  private selectionChangeCallback: ((data: SelectionData | null) => void) | null = null;

  /**
   * Sets the callback for selection change events
   * @param callback - Function to call when selection changes
   */
  onSelectionChange(callback: (data: SelectionData | null) => void): void {
    this.selectionChangeCallback = callback;
  }

  /**
   * Selects an object
   * @param object - Object to select
   */
  select(object: SelectableObject): void {
    // If clicking the same object, deselect it
    if (this.selectedObject && 
        this.selectedObject.id === object.id && 
        this.selectedObject.type === object.type) {
      this.clearSelection();
      return;
    }

    this.selectedObject = object;
    this.notifySelectionChange();
  }

  /**
   * Clears the current selection
   */
  clearSelection(): void {
    this.selectedObject = null;
    this.notifySelectionChange();
  }

  /**
   * Gets the currently selected object
   * @returns Currently selected object or null
   */
  getSelectedObject(): SelectableObject | null {
    return this.selectedObject;
  }

  /**
   * Notifies listeners about selection changes
   */
  private notifySelectionChange(): void {
    if (this.selectionChangeCallback) {
      if (this.selectedObject) {
        const selectionData: SelectionData = {
          type: this.selectedObject.type,
          displayName: this.selectedObject.displayName,
          position: this.selectedObject.position,
          info: this.selectedObject.getSelectionInfo()
        };
        this.selectionChangeCallback(selectionData);
      } else {
        this.selectionChangeCallback(null);
      }
    }
  }
} 