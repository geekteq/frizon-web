#!/bin/bash
# Start PHP dev server, accessible on LAN
# Usage: ./serve.sh [port]
# Then open http://<your-lan-ip>:<port> from any device on your network

PORT=${1:-8080}
IP="0.0.0.0"

echo "Starting Frizon dev server..."
echo "  Local:   http://localhost:$PORT"
echo "  LAN:     http://$(ipconfig getifaddr en0 2>/dev/null || hostname -I 2>/dev/null | awk '{print $1}'):$PORT"
echo ""
echo "Press Ctrl+C to stop."
echo ""

php -S "$IP:$PORT" -t public/
