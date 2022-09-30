import $ from "jquery";

import './styles/othello.css';

// start the Stimulus application
import './bootstrap';

// require('@fortawesome/fontawesome-free/css/all.min.css');
// require('@fortawesome/fontawesome-free/js/all.js');

let lightCount, darkCount;
let moves = [];

$(document).ready(function() {
    initSize();
    initReset();
    initBack();
});

const initReset = function () {
    $('button#buttonReset').on('click', () => {
        $('tbody#othelloTableBody').children().remove();
        initSize();
    })
}

const initBack = function () {
    $('button#buttonBack').on('click', () => {
        moves = JSON.parse(localStorage.getItem('moves'));
        const lastMove = moves.pop();
        localStorage.setItem('moves',JSON.stringify(moves));

        if(lastMove) {
            showChanges(lastMove);

            $('i').unbind();

            $('i.fa.fa-circle-thin').on('click', (e) => {
                moveResponse($('#' + e.target['id']).attr('data'));
            });
        }
    });
}

const initSize = function () {
    const container = document.querySelector('#container');
    const divButton = document.createElement('div');
    const six = document.createElement('button');
    const eight = document.createElement('button');
    const divRow = document.querySelector('#divRow');

    divButton.setAttribute('id', 'divButton')
    divButton.setAttribute('class', 'd-flex justify-content-center');
    six.setAttribute('class', 'btn btn-warning');
    eight.setAttribute('class', 'btn btn-success');
    six.setAttribute('data', 'size');
    eight.setAttribute('data', 'size');
    six.textContent = "6x6";
    eight.textContent = "8x8";
    divRow.setAttribute('style', 'display: none;')

    container.appendChild(divButton);
    divButton.appendChild(six);
    divButton.appendChild(eight);

    $('button.btn[data="size"]').on('click', (e) => {
        six.setAttribute('style', 'display: none;')
        eight.setAttribute('style', 'display: none;')

        localStorage.setItem('size', e.target.className === 'btn btn-warning' ? '66' : '88');
        initSide();
    })
}

const initSide = function () {
    const container = document.querySelector('#container');
    const divButton = document.querySelector('div#divButton');
    const light = document.createElement('button');
    const dark = document.createElement('button');
    const divRow = document.querySelector('#divRow');

    light.setAttribute('class', 'btn btn-light');
    dark.setAttribute('class', 'btn btn-dark');
    light.setAttribute('data', 'side');
    dark.setAttribute('data', 'side');
    light.textContent = "FEHÉR";
    dark.textContent = "FEKETE";

    divButton.appendChild(light);
    divButton.appendChild(dark);

    $('button.btn[data="side"]').on('click', (e) => {
        divButton.remove();
        divRow.setAttribute('style', 'display: show;')

        localStorage.setItem('side', e.target.className === 'btn btn-light' ? 'white' : 'black');
        $("i[game='0']").each((e, object) => {
            $(object).attr('class', 'fa fa-circle');
        })
        $("i[game='0'][side='" + localStorage.getItem('side') + "']").each((e, object) =>{
            $(object).attr('class', 'fa fa-circle was');
        })

        initBoard();
    })
}

const initBoard = function () {
    $.ajax({
        url:"othello/init",
        type: "POST",
        dataType: "json",
        data: {
            'size': localStorage.getItem('size'),
        },
        async: true,
        success: function (data)
        {
            console.log(data);
            const list = data['table'];
            const sides = data['sides'];

            lightCount = darkCount = 0;
            moves = [];


            const tableBody = document.getElementById('othelloTableBody');

            for (let i = 1; i <= Math.floor(localStorage.getItem('size') / 10); i++)
            {
                const tr = document.createElement('tr');
                tableBody.appendChild(tr);

                for(let j = 1; j <= localStorage.getItem('size') % 10; j++)
                {
                    const position = i * 100 + j;
                    const found = list.find(field => field['position'] === position);

                    const td = document.createElement('td');
                    const field = document.createElement('i');

                    td.setAttribute('class', 'text-center align-middle');
                    field.setAttribute('id',found['uuid']);
                    field.setAttribute('class', found['status']);
                    field.setAttribute('side', found['side']);
                    field.setAttribute('data', position.toString())
                    field.setAttribute('game', '1');

                    tr.appendChild(td);
                    td.appendChild(field);

                    if(found['side'] === 'white') {
                        lightCount++;
                    } else if(found['side'] == 'black') {
                        darkCount++;
                    }
                }
            }
            $('#lightCount').text(' ' + lightCount);
            $('#darkCount').text(' ' + darkCount);
            $('#lightRemainder').text(' ' + sides['light']);
            $('#darkRemainder').text(' ' + sides['dark']);
            localStorage.setItem('sides', JSON.stringify(sides));

            if(localStorage.getItem('side') === 'white') {
                moveResponse();
            } else {
                moves.push(data);
                localStorage.setItem('moves', JSON.stringify(moves));
            }



            $('i').unbind();

            $('i.fa.fa-circle-thin').on('click', (e) => {
                moveResponse($('#' + e.target['id']).attr('data'));
            })
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(textStatus, errorThrown);
        }
    });
}

const moveResponse = function(position = '') {
    const fieldList = [];
    $("i[game='1']").each((index, object) => {
        fieldList.push({
            'uuid': object.id,
            'status': object.className,
            'side': $(object).attr('side'),
            'position': $(object).attr('data')
        });
    })

    $.ajax({
        url:"othello/move",
        type: "POST",
        dataType: "json",
        data: {
            'field': position,
            'board': fieldList,
            'side' : localStorage.getItem('side'),
            'sides': JSON.parse(localStorage.getItem('sides'))
        },
        async: true,
        success: function (data)
        {
            console.log(data);
            if(data['end'] !== undefined) {
                window.alert('GameOver!')
            }

            showChanges(data);

            $('i').unbind();

            $('i.fa.fa-circle-thin').on('click', (e) => {
                saveMoves(data);
                moveResponse($('#' + e.target['id']).attr('data'));
            })
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(textStatus, errorThrown);
        }
    });
}

const showChanges = function (data) {
    const board = data['table'];
    const sides = data['sides'];
    lightCount = darkCount = 0;

    $("i[game='1']").each((index, object) => {
        const field = board.find((item) => {
            return item['uuid'] === object.id;
        });

        $(object).attr('class', field['status']);
        $(object).attr('side', field['side']);
        $('#lightRemainder').text(' ' + sides['light']);
        $('#darkRemainder').text(' ' + sides['dark']);
        localStorage.setItem('sides', JSON.stringify(sides));

        if(field['side'] === 'white') {
            lightCount++;
        } else if(field['side'] == 'black') {
            darkCount++;
        }
    });

    $('#lightCount').text(' ' + lightCount);
    $('#darkCount').text(' ' + darkCount);
}

const saveMoves = function (data) {
    moves = JSON.parse(localStorage.getItem('moves'));
    moves.push(data);
    localStorage.setItem('moves',JSON.stringify(moves));
}


