Self-hosted runner for Laravel Dusk (Stofgard)
===============================================

Overview
--------
This repository includes tools to setup a self-hosted runner suitable for executing Laravel Dusk (Chrome-based) browser tests.

Files added:
- `deploy/runner/bootstrap-dusk-runner.sh` — bootstrap script to install Chrome, ChromeDriver, PHP, Composer and configure `chromedriver.service`.
- `deploy/runner/chromedriver.service` — systemd unit enabling chromedriver as a service at port 9515.
- `.github/workflows/dusk-self-hosted.yml` — example workflow that runs on runners labeled `self-hosted, linux, dusk`.

Prerequisites
-------------
- Ubuntu 22.04+ recommended
- At least 4 GB RAM (8 GB recommended) and a few GB free disk space
- A GitHub repository or org admin token to register the runner (see GitHub docs)

Bootstrap & register the runner (high level)
-------------------------------------------
1. Provision a VM (Ubuntu) and SSH in.
2. Upload `deploy/runner/bootstrap-dusk-runner.sh` and run it as root:
   sudo bash bootstrap-dusk-runner.sh

   The script will install PHP, Composer, Chrome, and chromedriver, and create a systemd unit `chromedriver` that listens on port 9515.

3. Register the GitHub Actions self-hosted runner in the repository or organization (follow GitHub docs):
   - Create a new runner → you'll get a `config.sh` command and a registration token.
   - Run the config script as a non-root user (e.g., `runner`), add labels `self-hosted, linux, dusk`.
   - Install and start the `svc.sh` script to run the runner as a service.

4. Verify chromedriver is running:
   curl http://127.0.0.1:9515/status

5. In the repo, trigger the workflow `.github/workflows/dusk-self-hosted.yml` (manually via Actions UI or open a PR). It will run on the self-hosted runner and run Dusk against chromedriver.

Notes & troubleshooting
-----------------------
- Chrome/ChromeDriver versions must be compatible; the bootstrap script tries to download the matching chromedriver major version.
- If tests require `--no-sandbox` or other Chrome args, set `DUSK_CHROME_ARGS` env var in the workflow or on the runner env.
- Check `journalctl -u chromedriver` and `systemctl status chromedriver` for chromedriver service logs.
- If you prefer containerized Selenium, the current repo also includes an example workflow that uses a Selenium service container; but self-hosted runner gives more consistent environment.

Security
--------
- The registration token is short-lived and should be handled per GitHub docs.
- Avoid running additional untrusted workloads on the same runner where sensitive secrets are present.

If you want, I can also provide a sample `sudo`-friendly systemd service for the GitHub runner itself and an example cloud-init to bootstrap a VM end-to-end.
