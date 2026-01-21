#!/usr/bin/env bash
set -euo pipefail

# Bootstrap script to prepare an Ubuntu runner for Laravel Dusk tests
# Usage (run as root or with sudo):
#   sudo bash bootstrap-dusk-runner.sh
# Notes:
# - This script installs Chrome, ChromeDriver, PHP, Composer and creates a systemd service for chromedriver.
# - It does NOT register the GitHub Actions runner; follow README steps to register the runner with your repo/org.

# Detect distro
if ! command -v apt-get >/dev/null; then
  echo "Unsupported system: apt-get not found. This script targets Ubuntu/Debian." >&2
  exit 1
fi

export DEBIAN_FRONTEND=noninteractive
apt-get update -y

# Install required packages
apt-get install -y --no-install-recommends \
  ca-certificates curl wget gnupg unzip lsb-release sudo git build-essential \
  php8.3 php8.3-cli php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-sqlite3

# Composer (install if not present)
if ! command -v composer >/dev/null; then
  EXPECTED_SIGNATURE=$(curl -s https://composer.github.io/installer.sig)
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php composer-setup.php --quiet
  php -r "unlink('composer-setup.php');"
  mv composer.phar /usr/local/bin/composer
  chmod +x /usr/local/bin/composer
fi

# Install Google Chrome
if ! command -v google-chrome >/dev/null; then
  wget -q -O - https://dl.google.com/linux/linux_signing_key.pub | apt-key add -
  echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" > /etc/apt/sources.list.d/google-chrome.list
  apt-get update -y
  apt-get install -y --no-install-recommends google-chrome-stable
fi

# Ensure Chrome installed
CHROME_VERSION=$(google-chrome --product-version 2>/dev/null || true)
echo "Installed Google Chrome: ${CHROME_VERSION}"
CHROME_MAJOR=$(echo "$CHROME_VERSION" | cut -d. -f1)

# Install matching ChromeDriver
CHROMEDRIVER_BIN=/usr/local/bin/chromedriver
if [ ! -x "$CHROMEDRIVER_BIN" ]; then
  LATEST=$(curl -sS chromedriver.storage.googleapis.com/LATEST_RELEASE_${CHROME_MAJOR})
  if [ -z "$LATEST" ]; then
    echo "Could not detect chromedriver version for Chrome major ${CHROME_MAJOR}; falling back to LATEST_RELEASE" >&2
    LATEST=$(curl -sS chromedriver.storage.googleapis.com/LATEST_RELEASE)
  fi
  echo "Downloading chromedriver version $LATEST"
  wget -q -N https://chromedriver.storage.googleapis.com/${LATEST}/chromedriver_linux64.zip -O /tmp/chromedriver_linux64.zip
  unzip -o /tmp/chromedriver_linux64.zip -d /tmp
  mv -f /tmp/chromedriver /usr/local/bin/chromedriver
  chmod +x /usr/local/bin/chromedriver
fi

# Create chromedriver systemd unit (runs as runner user by default)
RUNNER_USER=${RUNNER_USER:-runner}
cat >/etc/systemd/system/chromedriver.service <<'UNIT'
[Unit]
Description=ChromeDriver Service for Laravel Dusk
After=network.target

[Service]
Type=simple
User=${RUNNER_USER}
ExecStart=/usr/local/bin/chromedriver --port=9515 --whitelisted-ips='' --url-base=/wd/hub
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
UNIT

# Make sure the runner user exists
if ! id -u "$RUNNER_USER" >/dev/null 2>&1; then
  echo "Creating user $RUNNER_USER"
  useradd -m -s /bin/bash "$RUNNER_USER"
fi

systemctl daemon-reload
systemctl enable --now chromedriver.service || true
sleep 1
if systemctl is-active --quiet chromedriver.service; then
  echo "chromedriver.service started"
else
  echo "chromedriver.service not active; check journalctl -u chromedriver" >&2
fi

# Optional: Print simple readiness check
echo "Chromedriver status (HTTP):"
curl -sS http://127.0.0.1:9515/status || true

# Instructions to install GitHub Actions runner (NOT automated here):
cat <<'INSTR'

Next steps (manual):
1. Create a runner token in GitHub repository or organization settings (Settings → Actions → Runners → New self-hosted runner).
2. Download and configure the runner as the $RUNNER_USER user. Example:
   sudo -u $RUNNER_USER -i
   mkdir -p ~/actions-runner && cd ~/actions-runner
   # follow https://github.com/actions/runner#self-hosted-runner-setup
3. When configuring the runner, add labels: `self-hosted`, `linux`, `dusk`.
4. Start the runner service using the provided runner setup commands.

After registration, verify the runner is online in GitHub UI and run the example workflow `.github/workflows/dusk-self-hosted.yml`.
INSTR

echo "Bootstrap complete."
