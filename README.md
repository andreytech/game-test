# Game Test Backend

A PHP/Symfony-based backend for a Keno-style number drawing game with real-time updates and house edge validation.

## Features

- **RNG**: Draws 20 unique numbers between 1-80 for each round
- **Player Input**: Supports 2-10 numbers per round with manual selection and auto-pick
- **Stakes Management**: Adjustable stakes from 0.1 to 10 units with 0.1 granularity
- **Round Reset/Replay**: Option to replay rounds with same numbers or autopick new ones
- **House Edge**: Maintains 5-8% house advantage with validation
- **Real-time Updates**: Live game state information during rounds
- **In-memory Storage**: Simple, efficient state management

## Installation

1. Install dependencies:
```bash
composer install
```

2. Configure environment variables:
```bash
# Copy the example environment file
cp env.example .env

# Edit .env file with your settings if needed
# The default values should work for development
```

3. Start the Symfony development server:
```bash
symfony server:start
```

4. The API will be available at `http://localhost:8000/api/game/`

Alternatively, you can use the built-in PHP server:
```bash
php -S localhost:8000 -t public
```

## API Endpoints

### Game Management

- `POST /api/game/create` - Create a new game
- `GET /api/game/{gameId}/state` - Get current game state
- `POST /api/game/{gameId}/replay` - Replay a finished game

### Player Actions

- `POST /api/game/{gameId}/numbers` - Set player numbers manually
- `POST /api/game/{gameId}/autopick` - Auto-pick player numbers
- `POST /api/game/{gameId}/stake` - Set game stake
- `POST /api/game/{gameId}/draw` - Draw numbers and finish game

### Game Information

- `GET /api/game/player/{playerId}` - Get all games for a player
- `GET /api/game/active` - Get all active games
- `GET /api/game/validation/house-edge` - Validate house edge calculations

## Usage Examples

### API Examples

#### Create a new game
```bash
curl -X POST http://localhost:8000/api/game/create \
  -H "Content-Type: application/json" \
  -d '{"playerId": "player1"}'
```

#### Set player numbers
```bash
curl -X POST http://localhost:8000/api/game/{gameId}/numbers \
  -H "Content-Type: application/json" \
  -d '{"numbers": [1, 5, 10, 15, 20]}'
```

#### Auto-pick numbers
```bash
curl -X POST http://localhost:8000/api/game/{gameId}/autopick \
  -H "Content-Type: application/json" \
  -d '{"count": 5}'
```

#### Set stake
```bash
curl -X POST http://localhost:8000/api/game/{gameId}/stake \
  -H "Content-Type: application/json" \
  -d '{"stake": 2.5}'
```

#### Draw numbers
```bash
curl -X POST http://localhost:8000/api/game/{gameId}/draw
```

#### Get game state
```bash
curl http://localhost:8000/api/game/{gameId}/state
```

## House Edge Validation

The system maintains a house edge between 5-8% through carefully calculated pay tables. You can validate the house edge for different number combinations:

```bash
curl http://localhost:8000/api/game/validation/house-edge
```

This returns the theoretical house edge for each number of picks (2-10) to ensure the house always maintains its advantage.

## Game Flow

1. Create a game with optional player ID
2. Set player numbers (manual or auto-pick)
3. Set stake amount
4. Draw numbers to complete the round
5. View results and payout
6. Optionally replay with same numbers or start new game



## Testing and Demo Commands

The system includes several console commands for testing and demonstration:

### Game Seeder
Create test data and run simulations:
```bash
# Create 5 players with 10 games each
php bin/console app:game:seed --players=5 --games-per-player=10

# Run with simulation analysis
php bin/console app:game:seed --simulate

# Custom stake range
php bin/console app:game:seed --stake-range=1.0-5.0
```

### Game Tester
Test API endpoints and functionality:
```bash
# Run 5 test iterations
php bin/console app:game:test --iterations=5

# Verbose output
php bin/console app:game:test --verbose
```

### Interactive Demo
Interactive game demonstration:
```bash
# Interactive mode
php bin/console app:game:demo

# Auto mode with 10 rounds
php bin/console app:game:demo --auto --rounds=10
```

### Available Commands
```bash
# List all available commands
php bin/console list app:game

# Get help for specific command
php bin/console app:game:seed --help
```

## Testing

The system is designed to be deterministic for testing purposes. Given the same RNG seed and player inputs, results will be consistent, making it suitable for automated testing and validation.
