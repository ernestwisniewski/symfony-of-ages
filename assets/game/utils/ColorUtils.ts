/**
 * Color utility functions for game rendering
 */
export class ColorUtils {
  /**
   * Convert HSV to RGB color
   * @param h - Hue (0-360)
   * @param s - Saturation (0-1)
   * @param v - Value (0-1)
   * @returns RGB color as number (0xRRGGBB)
   */
  static hsvToRgb(h: number, s: number, v: number): number {
    const c = v * s;
    const x = c * (1 - Math.abs((h / 60) % 2 - 1));
    const m = v - c;

    let r = 0, g = 0, b = 0;

    if (h >= 0 && h < 60) {
      r = c; g = x; b = 0;
    } else if (h >= 60 && h < 120) {
      r = x; g = c; b = 0;
    } else if (h >= 120 && h < 180) {
      r = 0; g = c; b = x;
    } else if (h >= 180 && h < 240) {
      r = 0; g = x; b = c;
    } else if (h >= 240 && h < 300) {
      r = x; g = 0; b = c;
    } else if (h >= 300 && h < 360) {
      r = c; g = 0; b = x;
    }

    const red = Math.round((r + m) * 255);
    const green = Math.round((g + m) * 255);
    const blue = Math.round((b + m) * 255);

    return (red << 16) | (green << 8) | blue;
  }

  /**
   * Get color for owner ID (simple hash-based color generation)
   * @param ownerId - Owner identifier string
   * @returns RGB color as number (0xRRGGBB)
   */
  static getColorForOwner(ownerId: string): number {
    let hash = 0;
    for (let i = 0; i < ownerId.length; i++) {
      const char = ownerId.charCodeAt(i);
      hash = ((hash << 5) - hash) + char;
      hash = hash & hash; // Convert to 32bit integer
    }

    // Generate a color from the hash
    const hue = Math.abs(hash) % 360;
    return ColorUtils.hsvToRgb(hue, 0.8, 0.9);
  }
} 