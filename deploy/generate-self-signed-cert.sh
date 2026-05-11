#!/bin/bash
set -e

if [ "$EUID" -ne 0 ]; then
  echo "Kérlek, futtasd rootként."
  exit 1
fi

DOMAIN=${1}
if [ -z "$DOMAIN" ]; then
    echo "Használat: sudo $0 <domain>"
    exit 1
fi

SSL_PATH="/etc/ssl"
KEY_FILE="${SSL_PATH}/private/${DOMAIN}.key"
CERT_FILE="${SSL_PATH}/certs/${DOMAIN}.pem"

echo ">>> Önaláírt tanúsítvány generálása a(z) ${DOMAIN} domainhez..."

openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout "${KEY_FILE}" \
    -out "${CERT_FILE}" \
    -subj "/C=HU/ST=Budapest/L=Budapest/O=Local Development/CN=${DOMAIN}"

echo ">>> Tanúsítvány sikeresen létrehozva: ${CERT_FILE}"
