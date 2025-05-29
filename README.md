# Symfony of ages

**Symfony of Ages** is a turn-based browser game inspired by *Civilization*. Players expand their influence, explore a hex-based map, and engage in strategic gameplay to dominate the neighborhood.

## Project Goals

- Turn-based gameplay on a hexagonal map
- 2D visual style inspired by *Civilization*
- Playable directly in the browser

## Tech Stack

- **Symfony 7.3** – Backend framework (API & game logic)
- **Symfony UX (Live Components, Turbo)** – Interactive frontend behavior without heavy JavaScript SPA
- **PixiJS** – Fast WebGL rendering of the game map and units
- **TypeScript** – Strongly-typed logic for frontend interactions
- **HTML5/CSS3** – Core UI structure

## Features (WIP)

- Dynamic hex map rendering
- Terrain types with different strategic values (plains, forest, mountain, swamp, water, desert)
- Turn-based movement and interaction system
- Fog of war
- Event-driven gameplay

## Setup & Development

```bash
git clone https://github.com/ernestwisniewski/symfony-of-ages.git
cd symfony-of-ages

# Backend setup
composer install

# Frontend setup
npm install
npm run dev

# Start Symfony local server
symfony serve
