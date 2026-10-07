#!/bin/sh
set -e

DOMAIN="${DOMAIN:-guatape.local}"
CERT_DIR="/root/.local/share/mkcert"

if [ ! -f "$CERT_DIR/cert.pem" ]; then
  echo "Generating SSL certificates for $DOMAIN..."
  mkcert -install
  cd "$CERT_DIR"
  # The mkcert in this image predates -cert-file/-key-file: it writes
  # "<domain>+<n>.pem" and "<domain>+<n>-key.pem", renamed below to the names
  # nginx expects.
  mkcert "$DOMAIN" "*.${DOMAIN}" localhost 127.0.0.1 ::1
  mv "${DOMAIN}"+*-key.pem key.pem
  mv "${DOMAIN}"+*.pem cert.pem
  echo "Certificates generated successfully."
else
  echo "Certificates already exist, skipping generation."
fi
