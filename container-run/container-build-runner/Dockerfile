FROM python:3.14
COPY requirement.txt /tmp
RUN pip install -r /tmp/requirement.txt
RUN rm -f /tmp/requirement.txt

COPY *.py /media
CMD bash
