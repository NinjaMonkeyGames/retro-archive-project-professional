#!/bin/bash

LOG_FILE="$1"

if [ -z "$LOG_FILE" ] || [ ! -f "$LOG_FILE" ]; then
  echo "Error: Log file not provided or does not exist."
  exit 1
fi

# Search for [FAIL] in the verbose output log
if grep -q "\[FAIL\]" "$LOG_FILE"; then
  echo "Test failure detected [FAIL] found in output."
  exit 1
else
  echo "No failures found. Tests passed successfully."
  exit 0
fi