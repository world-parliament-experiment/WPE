The World Parliament Experiment
==============================

This distribution is based on Symfony Version 5.4. It was developed to run under PHP 8.1

The BETA version is currently running [**here**][1].

If you want to collaborate please create a branch from the main branch "develop". 

For more information on desirability and potential of this project please see the Essay [**United Humans: Internet voting for a world parliament and a new world organization**][6].

Enjoy!

---

# Docker Installation

By following the installation steps below, it is possible to view a local version of the World Parliament Experiment website. This allows you to change the website's content and test new features before they are deployed to the actual website.

## Prerequisites
- Git version control
- Docker (Installation: https://docs.docker.com/engine/install/)
- Docker Compose (Installation: https://docs.docker.com/compose/install/)

## Quick Start (Docker)

1. **Clone the repository** (if you haven't already):
    ```bash
    git clone https://github.com/world-parliament-experiment/WPE.git
    cd WPE
    ```

2. **Build and start the containers**:
    ```bash
    docker-compose up -d --build
    ```

3. **Install dependencies**:
    ```bash
    docker-compose exec web composer install
    ```

4. **Initialize the database schema**:
    ```bash
    docker-compose exec web php bin/console doctrine:schema:create
    ```

5. **Load data fixtures** (Optional):
    ```bash
    docker-compose exec web php bin/console doctrine:fixtures:load --no-interaction
    ```

You can now open [**http://localhost:8080**](http://localhost:8080) in your browser. 
Login with: `borchert` / `test` (Admin)

---

The World Parliament Experiment is supported by [**Democracy Without Borders**][3] and one of its main initiatives as a [**Global Voting Platform**][4].

[1]:  https://www.world-parliament.org
[2]:  http://worldparliament.a.wiki-site.com/index.php/Main_Page
[3]:  https://www.democracywithoutborders.org/
[4]:  https://www.democracywithoutborders.org/gdve-it/
[5]:  http://www.world-parliament.org/
[6]:  https://www.democracywithoutborders.org/files/DWBDPRT2018.pdf
