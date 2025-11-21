#!/usr/bin/env sh
# Simple wait-for-it script: wait for HOST PORT, then exec the rest of the args
# Usage: wait-for-it.sh <host> <port> <command> [args...]

HOST="$1"
PORT="$2"
shift 2

RETRIES=${RETRIES:-60}
SLEEP=${SLEEP:-1}

if [ -z "$HOST" ] || [ -z "$PORT" ]; then
  echo "Usage: wait-for-it.sh <host> <port> <command> [args...]"
  exit 1
fi

count=0
until nc -z "$HOST" "$PORT" >/dev/null 2>&1; do
  count=$((count+1))
  if [ "$count" -ge "$RETRIES" ]; then
    echo "Timeout waiting for $HOST:$PORT"
    exit 1
  fi
  echo "Waiting for $HOST:$PORT ($count/$RETRIES)..."
  sleep "$SLEEP"
done

echo "$HOST:$PORT is available. Starting command..."
exec "$@"
