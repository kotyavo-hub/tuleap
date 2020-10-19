include ../.env
export

DHOST := $(shell docker run --rm bash -c 'ip -4 route show default | cut -d" " -f3')
PWD   := $(shell pwd)

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

.PHONY: prepare
prepare:
	docker-compose run --rm app composer install

.PHONY: update
update:
	docker-compose run --rm app composer update

.PHONY: transfer
transfer:
	DHOST=$(DHOST) docker-compose run --rm app php ./bin/transfer.php $(with)

.PHONY: partial-transfer
partial-transfer:
	make transfer with="--include=$(of)"

.PHONY: reload-redmine-db
reload-redmine-db:
	make transfer with="--sql=':schema/redmine.db.sql' --sql='redmine:redmine.sql'"

.PHONY: reload-tuleap-db
reload-tuleap-db:
	make transfer with="--sql='tuleap:tuleap.sql'"

.PHONY: reload-db
reload-db: reload-redmine-db reload-tuleap-db
