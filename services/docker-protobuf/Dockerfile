# 基础镜像
# FROM <image>
# FROM <image>:<tag>
# FROM <image>@<digest>
FROM ubuntu:latest

# 镜像拥有者
# MAINTAINER <name>
MAINTAINER fengsha


RUN mkdir /workspace

ADD ./ /workspace

## 

# 设置容器启动时运行的命令,常驻命令避免立马退出
CMD ["tail", "-f", "/dev/null"]