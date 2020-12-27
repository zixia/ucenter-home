# UCenter+UCHome+Discuz

UCenter1.5.1/UCHome2.0/Discuz!7.2 集成安装包  

GitHub Repo: <https://github.com/zixia/ucenter-home>

## Screenshot

![UCenter Home](https://raw.githubusercontent.com/17salsa/ucenter-home/master/uchome.gif)

## Download (Mirror from)

* http://www.comsenz.com/downloads/install/uchome
* http://download.comsenz.com/UC_UCH_DZ/UC1.5.1_UCH2.0_DZ7.2_SC_UTF8.zip

## Install

1. read "readme.txt" first
1. fix file permission:

    ```bash
    sudo chmod -R 777 ucenter/data/ home/config.php home/attachment/ home/data/ home/uc_client/data/ bbs/config.inc.php bbs/attachments/ bbs/templates/ bbs/forumdata/ bbs/uc_client/data/
    ```

1. setup by visit http://ip/
 1. /install
 1. /home/install
 1. /bbs/install

## Docker

```sh
docker-compose up
```

## Maintainer

[Huan](https://github.com/huan) [(李卓桓)](http://linkedin.com/in/zixia), Chair of [17SALSA](https://www.17salsa.com), <zixia@zixia.net>

[![Profile of Huan LI (李卓桓) on StackOverflow](https://stackoverflow.com/users/flair/1123955.png)](https://stackoverflow.com/users/1123955/huan)

## Copyright & License

- Code & Docs © 2010-2021 Comsenz & 17SALSA
- Code released under the Apache-2.0 License
- Docs released under Creative Commons
