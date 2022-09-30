<?php

namespace App\Controller;

use App\Entity\OthelloField;
use Doctrine\ORM\Query\Expr\Math;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OthelloController extends AbstractController
{
    /**
     * @Route("/othello", name="app_othello")
     */
    public function index(): Response
    {
        return $this->render('othello/index.html.twig', [
            'controller_name' => 'OthelloController',
        ]);
    }

    /**
     * @Route("/othello/init", name="othelloInit")
     */
    public function othelloInit(): Response
    {
        if(!isset($_SESSION))
        {
            session_start();
        }

        $board = [];

        $size = $_POST['size'] ? $_POST['size'] : 8;

        $column = intdiv($size,10);
        $columnHalf = intdiv($column,2);
        $row = $size % 10;
        $rowHalf = intdiv($row, 2);

        $_SESSION['white'] = $columnHalf * $column;
        $_SESSION['black'] = $columnHalf * $column;

        for($i = 1; $i <= $column; $i++)
        {
            for($j = 1; $j <= $row; $j++)
            {
                $newField = null;
                $new = true;
                $newField = new OthelloField($i * 100 + $j);

                while($new)
                {
                    $new = false;
                    $newField->generateUuid();

                    foreach ($board as $field)
                    {
                        if($field->getUuid() == $newField->getUuid())
                        {
                            $new = true;
                        }
                    }
                }


                if(($i == $columnHalf || $i == $columnHalf + 1) &&
                    ($j == $rowHalf || $j == $rowHalf + 1))
                {
                    if($i == $j)
                    {
                        $newField->setSide('white');
                        $newField->setStatus('fa fa-circle');
                    }
                    else
                    {
                        $newField->setSide('black');
                        $newField->setStatus('fa fa-circle');
                    }
                }
                $board[] = $newField;
            }
        }

        $result = $this->resultToJSON($this->othelloSetMoveableFields($board, 'black'));
        return new JsonResponse($result);
    }

    /**
     * @param array
     * @return array
     */
    private function resultToJSON($list) :array
    {
        $responseList = [];
        foreach ($list as $field)
        {
            $responseList['table'][] = [
                'position' => $field->getPosition(),
                'uuid' => $field->getUuid(),
                'side' => $field->getSide(),
                'status' => $field->getStatus()
            ];

            $responseList['sides'] = [
                'light' => $_SESSION['white'],
                'dark' => $_SESSION['black']
            ];
        }

        return $responseList;
    }

    /**
     * @param array $jsonResponse
     * @return array
     */
    private function jsonToResponse(array $jsonResponse) :array
    {
        $board = [];
        if(isset($jsonResponse['sides'])) {
            $_SESSION['white'] = $jsonResponse['sides']['light'];
            $_SESSION['black'] = $jsonResponse['sides']['dark'];
        }

        foreach ($jsonResponse['board'] as $item) {
            $field = new OthelloField($item['position']);
            $field->setUuid($item['uuid']);
            $field->setStatus($item['status'] == 'fa fa-circle was' ? 'fa fa-circle' : $item['status']);
            $field->setSide($item['side']);

            $board[] = $field;
        }

        return [
            'board' => $board,
            'field' => $jsonResponse['field'],
            'side' => $jsonResponse['side']
        ];
    }

    /**
     * @param array
     * @return array
     */
    private function othelloListSeparator(array $list): array
    {
        $emptyFields = [];
        $onFields = [];
        $moveableFields = [];

        foreach ($list as $item) {
            if($item->getStatus() === "fa fa-fw") {
                $emptyFields[] = $item;
            } elseif ($item->getStatus() === "fa fa-circle-thin") {
                $moveableFields[] = $item;
            } else {
                $onFields[] = $item;
            }
        }

        return [
          'emptyFields' => $emptyFields,
          'onFields' => $onFields,
            'moveableFields' => $moveableFields
        ];
    }

    /**
     * @param array
     * @return array
     */
    private function othelloListMerging(array $list): array
    {
        return array_merge($list['emptyFields'], $list['moveableFields'], $list['onFields']);
    }

    /**
     * @param array $list
     * @param string $side
     * @return array
     */
    private function othelloSetMoveableFields(array $list, string $side): array
    {
        foreach ($list as $item) {
            if($item->getStatus() == 'fa fa-circle-thin') {
                $item->setStatus('fa fa-fw');
                $item->setScore(1);
                $item->setSide('');
            }
        }

        $separatedList = $this->othelloListSeparator($list);
        $keyEditors = OthelloField::getKeyEditors();
        $side = $side === 'black' ? 'white' : 'black';

        foreach ($separatedList['emptyFields'] as $emptyField) {
            foreach ($keyEditors as $keyEditor) {
                $i = 1;
                $condition = true;


                while($condition)
                {
                    $field = $this->othelloSearchFieldByPosition($separatedList['onFields'], $emptyField->getPosition() + $keyEditor * $i);
                    if($condition = $field !== false && $field->getSide() === $side) {
                        $i++;
                    }
                }

                if($i > 1 && $field && !$condition) {
                    $emptyField->setStatus('fa fa-circle-thin');
                    $emptyField->addScore($i-1);
                    $emptyField->setSide('');
                }
            }
            $emptyField->addScore(abs(455 - $emptyField->getPosition() / 1000));
        }


        return $this->othelloListMerging($separatedList);

    }

    /**
     * @param array $list
     * @param int $position
     * @return bool | OthelloField
     */
    private function othelloSearchFieldByPosition(array $list, int $position)
    {
        foreach ($list as $item) {
            if($item->getPosition() === $position)
            {
                return $item;
            }
        }

        return false;
    }

    /**
     * @Route("/othello/move", name="othelloMove")
     */
    public function othelloMove(): Response
    {
        if($_POST)
        {
            $response = $this->jsonToResponse($_POST);

            $sameSide = $response['side'];
            $opponentSide = $response['side'] == 'white' ? 'black' : 'white';

            if($response['field'] !== '') {
                $list = $this->moveToDos($response);
                $_SESSION[$sameSide]--;
            } else {
                $list = $response['board'];
            }

            $surveyedList = $this->othelloSetMoveableFields($list, $opponentSide);

            if(count($this->othelloListSeparator($surveyedList)['moveableFields']) > 0 && $_SESSION[$opponentSide] > 0)
            {
                $scoredList = $this->getMaxMoveableField($surveyedList, $sameSide);

                $movedList = $this->moveTodos($scoredList, true);

                $_SESSION[$opponentSide]--;
            }
            else {
                $movedList = $surveyedList;
            }


            $maFields = $this->othelloSetMoveableFields($movedList, $sameSide);

            if(count($this->othelloListSeparator($maFields)['moveableFields']) > 0 && $_SESSION[$sameSide] > 0)
            {
                return new JsonResponse($this->resultToJSON($maFields));
            }
            elseif ((count($this->othelloListSeparator($maFields)['moveableFields']) < 1 &&
                        count($this->othelloListSeparator($surveyedList)['moveableFields']) < 1) ||
                    ($_SESSION[$sameSide] < 1 && $_SESSION[$opponentSide] < 1)) {
                $result = $this->resultToJSON($maFields);
                $result['end'] = 'GameOver';
                return new JsonResponse($result);
            }
            else
            {
                $resultToJSON = $this->resultToJSON($maFields);

                $_POST = $this->jsonToResponse([
                    'board' => $resultToJSON,
                    'side' => $sameSide,
                    'field' => '',
                ]);

                return $this->othelloMove();
            }

        }



        return new JsonResponse([]);
    }

    /**
     * @param array $response
     * @param bool $show
     * @return array
     */
    private function moveTodos(array $response, bool $show = false) :array
    {
        $separatedList = $this->othelloListSeparator($response['board']);
        $movingField = $this->othelloSearchFieldByPosition($separatedList['moveableFields'], $response['field']);

        foreach (OthelloField::getKeyEditors() as $keyEditor) {
            $i = 1;
            $condition = true;
            $field = true;


            while($condition)
            {
                $field = $this->othelloSearchFieldByPosition($separatedList['onFields'], $movingField->getPosition() + $keyEditor * $i);
                if($condition = $field !== false && $field->getSide() === ($response['side'] == 'white' ? 'black' : 'white')) {
                    $i++;
                }
            }

            if($i > 1 && $field && !$condition) {
                while($i-- > 1) {
                    foreach ($separatedList['onFields'] as $onField) {
                        if($onField->getPosition() == $movingField->getPosition() + $keyEditor * $i)
                        {
                            $onField->setSide($response['side']);
                        }
                    }
                }
            }
        }

        foreach ($separatedList['moveableFields'] as $moveableField) {
            if($moveableField->getPosition() == $movingField->getPosition()) {
                $moveableField->setStatus($show ? 'fa fa-circle was' : 'fa fa-circle');
                $moveableField->setSide($response['side']);
            } else {
                $moveableField->setStatus('fa fa-fw');
                $moveableField->setSide('');
                $moveableField->setScore(1);
            }
        }


        return $this->othelloListMerging($separatedList);
    }

    /**
     * @param array $list
     * @param string $side
     * @return array
     */
    private function getMaxMoveableField(array $list, string $side) :array
    {
        $max = 0;
        $position = 0;

        foreach ($list as $item) {
            if($item->getStatus() == 'fa fa-circle-thin' && $item->getScore() > $max) {
                $max = $item->getScore();
                $position = $item->getPosition();
            }
        }

        return ['field' => $position,
                'board' => $list,
                'side' => $side == 'white' ? 'black' : 'white'];
    }
}
