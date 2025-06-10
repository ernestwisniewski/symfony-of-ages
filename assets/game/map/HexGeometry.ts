/**
 * Interface for position coordinates
 */
interface Position {
  x: number;
  y: number;
}

/**
 * HexGeometry class for hexagonal grid calculations and positioning
 * Provides utility methods for calculating hex positions and dimensions
 * Uses flat-top hexagon orientation with offset coordinate system
 */
export class HexGeometry {
  private static readonly HEX_HEIGHT_STEP_RATIO = 0.75;

  private readonly width: number;
  private readonly height: number;
  private readonly stepX: number;
  private readonly stepY: number;
  private readonly shiftX: number;

  /**
   * Creates a new HexGeometry instance with calculated dimensions
   *
   * @param size - The radius of the hexagon (distance from center to vertex)
   */
  constructor(size: number) {
    this.width = Math.sqrt(3) * size; // hex width (flat-top)
    this.height = 2 * size; // hex height
    this.stepX = this.width;
    this.stepY = this.height * HexGeometry.HEX_HEIGHT_STEP_RATIO;
    this.shiftX = this.stepX / 2;
  }

  /**
   * Calculates the pixel position for a hex at given grid coordinates
   * Uses offset coordinate system where odd rows are shifted horizontally
   *
   * @param row - The row index in the hex grid
   * @param col - The column index in the hex grid
   * @returns Position object with x and y pixel coordinates
   */
  calculatePosition(row: number, col: number): Position {
    const x = col * this.stepX + (row & 1 ? this.shiftX : 0);
    const y = row * this.stepY;

    return { x, y };
  }
}
