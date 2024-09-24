Seltzer CRM 0.5.5 - An open source CRM for hackerspaces
Copyright 2009-2018 Edward L. Platt <ed@elplatt.com>
Distributed under GPLv3 (see COPYING for more info)

## Contents ##
1. Overview
2. Installation and Usage

## Overview ##
Seltzer CRM is a tool for managing membership data at hackerspaces and similar
membership organizations.  It is designed to be useful to a typical hackerspace
administrator without any training, and easy to tailor to the needs of a
particular space through modularity rather than modification or configuration.

The current features are:
* Tracking of member contact and emergency contact info
* Tracking of membership levels and dates
* Automated Billing
* Tracking of RFID key assignments
* Track members' mentors
* Customizable permissions and roles

Why create another CRM?  There are a number of powerful CRMs out there already,
including the open source CiviCRM.  However, we found the complexity of existing
CRMs to be an obstacle to recruiting and training volunteers at member-run
organizations such as hackerspaces.  Seltzer CRM has a very basic feature set
tailored to the needs of a hackerspace.

Seltzer has been in production use at the i3 Detroit hackerspace in Ferndale, MI
since 2010-12-20.

## Installation and Usage ##
For more information on using Seltzer CRM, see the
[wiki](https://github.com/elplatt/seltzer/wiki).
Installation instructions are in the INSTALL file.


## Development ##

The suggested development mode is Docker and Docker Compose.

### Start a server

0. Install Docker
1. Run `docker compose up -d`
2. Open http://localhost:8088/
3. Login with `admin`/`pass`

This should hot reload and not require restarting for most changes.

### Stop the server

1. Run `docker compose down`

### Reset database

1. Run `docker compose down --volumes`
2. Run `docker compose up -d`

### Open a SQL prompt

1. Run `docker compose exec mysql mysql -psecret i3crm`

### View PHP logs

1. Run `docker compose logs -f php`
