/**
 * HexGeometry class for hexagonal grid calculations and positioning
 * Provides utility methods for calculating hex positions and dimensions
 * Uses flat-top hexagon orientation with offset coordinate system
 */
export class HexGeometry {
  /**
   * Creates a new HexGeometry instance with calculated dimensions
   * 
   * @param {number} size - The radius of the hexagon (distance from center to vertex)
   */
  constructor(size) {
    this.size = size;
    this.width = Math.sqrt(3) * size; // hex width (flat-top)
    this.height = 2 * size; // hex height
    this.stepX = this.width;
    this.stepY = this.height * 0.75;
    this.shiftX = this.stepX / 2;
  }

  /**
   * Calculates the pixel position for a hex at given grid coordinates
   * Uses offset coordinate system where odd rows are shifted horizontally
   * 
   * @param {number} row - The row index in the hex grid
   * @param {number} col - The column index in the hex grid
   * @returns {Object} Position object with x and y pixel coordinates
   * @returns {number} returns.x - X coordinate in pixels
   * @returns {number} returns.y - Y coordinate in pixels
   */
  calculatePosition(row, col) {
    const position = {
      x: col * this.stepX + (row & 1 ? this.shiftX : 0),
      y: row * this.stepY
    };
    
    return position;
  }

  /**
   * Gets the corner points for drawing a hexagon
   * Returns points for a flat-top hexagon starting from the top-right vertex
   * 
   * @returns {number[]} Array of x,y coordinates for the hexagon vertices
   */
  getCornerPoints() {
    const points = [];
    for (let i = 0; i < 6; i++) {
      const angle = (60 * i + 30) * Math.PI / 180;
      points.push(
        this.size * Math.cos(angle),
        this.size * Math.sin(angle)
      );
    }
    return points;
  }
}
