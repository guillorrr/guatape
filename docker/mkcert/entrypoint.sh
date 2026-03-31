#!/bin/sh
set -e

DOMAIN="${DOMAIN:-guatape.local}"
CERT_DIR="/root/.local/share/mkcert"

if [ ! -f "$CERT_DIR/cert.pem" ]; then
  echo "Generating SSL certificates for $DOMAIN..."
  mkcert -install
  mkcert -cert-file "$CERT_DIR/cert.pem" -key-file "$CERT_DIR/key.pem" \
    "$DOMAIN" "*.${DOMAIN}" localhost 127.0.0.1 ::1
  echo "Certificates generated successfully."
else
  echo "Certificates already exist, skipping generation."
fi
