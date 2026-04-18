# Deployment Plan: Autonomia VPS

This document outlines the steps for deploying the application to the VPS, setting up Docker, and configuring Traefik as a reverse proxy with Let's Encrypt SSL certificates via Cloudflare.

## Analysis and Execution Roadmap

1.  **Preparation**: Connect to the VPS via SSH as the `root` user.
2.  **Docker Installation**: Install Docker and its plugins (`docker-buildx-plugin`, `docker-compose-plugin`) using the official repository to ensure the latest versions are available.
3.  **Network Setup**: Create a Docker network named `proxy_public`. This is crucial for enabling communication between Traefik and other containers (the application and other services) and allowing Traefik to route traffic appropriately.
4.  **Traefik Configuration**:
    *   Create a directory for Traefik configuration (`/opt/traefik`).
    *   Create the `acme.json` file with correct permissions (`600`) to securely store Let's Encrypt certificates.
5.  **Docker Compose File**: Create a `docker-compose.yml` file for Traefik.
    *   Configure Traefik to listen on ports 80 and 443.
    *   Set up Cloudflare DNS challenge for automatic SSL generation using the provided API token.
    *   Ensure Traefik routes HTTP to HTTPS automatically.
6.  **Deployment**: Bring up the Traefik container using `docker compose up -d`.

### Considerations and Decisions:
*   The Cloudflare API Token has been provided securely and will be injected into the `docker-compose.yml` file as an environment variable (`CF_DNS_API_TOKEN`).
*   The Traefik configuration uses the `proxy_public` external network, which means any future containers that need routing by Traefik must also be attached to this network.
*   The email address `allisson@autonomia.app.br` will be used for Let's Encrypt certificate registration.
