#
# Credit: Huan LI <zixia@zixia.net> github.com/huan
#
.PHONY: test
test:
	./scripts/test.sh

.PHONY: build
build:
	docker build -t uchome .

.PHONY: run
run:
	docker run \
		--name uchome \
		--rm \
		-ti \
		--network bridge \
		-e UCHOME_MYSQL_HOST \
		-e UCHOME_MYSQL_USER \
		-e UCHOME_MYSQL_PASS \
		-e UCHOME_MYSQL_DATABASE \
		-p 8080:80 \
		-v /tmp:/var/www/admin/UploadFiles/ \
		--entrypoint bash \
		uchome

.PHONY: clean
clean:
	docker rmi uchome

.PHONY: version
version:
	./scripts/version.sh