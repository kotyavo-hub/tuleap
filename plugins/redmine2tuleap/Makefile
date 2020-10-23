include ../../.env
export

DHOST := $(shell docker run --rm bash -c 'ip -4 route show default | cut -d" " -f3')
PWD   := $(shell pwd)

.PHONY: prepare
prepare: dump-dbs prepare-database prepare-vendor

.PHONY: dump-redmine-db
dump-redmine-db:
	test -n "$(to)"
	ssh root@redmine.maximaster.ru "mysqldump -q redmine_new" > $(to)

.PHONY: dump-tuleap-db
dump-tuleap-db:
	test -n "$(to)"
	cd ../ && docker-compose exec db mysqldump -q -uroot -p$(MYSQL_ROOT_PASSWORD) tuleap 2>/dev/null > $(PWD)/$(to)
	sed -i '1d' $(to)

.PHONY: dump-dbs
dump-dbs:
	make dump-redmine-db to=redmine.sql
	make dump-tuleap-db to=tuleap.sql

.PHONY: prepare-vendor
prepare-vendor:
	docker-compose run --rm app composer install

.PHONY: update
update:
	docker-compose run --rm app composer update

.PHONY: transfer
transfer:
	DHOST=$(DHOST) docker-compose -f ./../../docker-compose.yml exec web tuleap redmine2tuleap:transfer $(with)

.PHONY: full-transfer
full-transfer: reload-redmine-db reload-tuleap-db data-transfer

.PHONY: debug-transfer
debug-transfer:
	make reload-tuleap-db
	make partial-transfer of="$(of)"

.PHONY: data-transfer
data-transfer:
	make partial-transfer of="*"

.PHONY: partial-transfer
partial-transfer:
	make transfer with="--include=$(of)"

.PHONY: reload-redmine-db
reload-redmine-db:
	make transfer with="--sql='redmine:redmine.sql'"

.PHONY: reload-tuleap-db
reload-tuleap-db:
	make transfer with="--sql='tuleap:tuleap.sql'"

.PHONY: reload-db
reload-db: reload-redmine-db reload-tuleap-db

.PHONY: prepare-database
prepare-database:
	make sql query="CREATE DATABASE IF NOT EXISTS redmine DEFAULT CHARACTER SET utf8;"
	make sql query="GRANT ALL PRIVILEGES ON redmine.* TO tuleapadm;"
	make sql query="FLUSH PRIVILEGES;"
	make sql query="SET GLOBAL max_allowed_packet = 2 * 1024 * 1024 * 1024;"

.PHONY: sql
sql:
	docker-compose -f ./../../docker-compose.yml exec db mysql -p$(MYSQL_ROOT_PASSWORD) -e "$(query)"
