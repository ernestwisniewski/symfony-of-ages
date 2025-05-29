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
  private size: number;
  private width: number;
  private height: number;
  private stepX: number;
  private stepY: number;
  private shiftX: number;

  /**
   * Creates a new HexGeometry instance with calculated dimensions
   * 
   * @param size - The radius of the hexagon (distance from center to vertex)
   */
  constructor(size: number) {
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
   * @param row - The row index in the hex grid
   * @param col - The column index in the hex grid
   * @returns Position object with x and y pixel coordinates
   */
  calculatePosition(row: number, col: number): Position {
    const x = col * this.stepX + (row & 1 ? this.shiftX : 0);
    const y = row * this.stepY;

    return { x, y };
  }

  /**
   * Gets the corner points for drawing a hexagon
   * Returns points for a flat-top hexagon starting from the top-right vertex
   * 
   * @returns Array of x,y coordinates for the hexagon vertices
   */
  getCornerPoints(): number[] {
    const points: number[] = [];
    for (let i = 0; i < 6; i++) {
      const angle = (60 * i + 30) * Math.PI / 180;
      points.push(
        this.size * Math.cos(angle),
        this.size * Math.sin(angle)
      );
    }
    return points;
  }

  /**
   * Calculates the vertices of a hexagon centered at the origin
   * Returns an array of x,y coordinates for drawing the hex shape
   *
   * @returns Array of coordinate pairs representing hex vertices
   */
  getHexVertices(): number[] {
    const vertices: number[] = [];
    
    for (let i = 0; i < 6; i++) {
      const angle = (Math.PI / 3) * i;
      const x = this.size * Math.cos(angle);
      const y = this.size * Math.sin(angle);
      vertices.push(x, y);
    }
    
    return vertices;
  }

  /**
   * Gets the size (radius) of the hexagons
   *
   * @returns The hexagon radius
   */
  getSize(): number {
    return this.size;
  }
}
