Feature: Getting News
  Scenario: I want to get News from API with ID
    Given I am a developer
    When I 'GET' request news from "http://localhost/api/news"
    When Id is "2"
    Then Response equal to
    """
    {
        "id": 2,
        "category_id": 2,
        "user_id": 1,
        "title": "OF",
        "content": "OF",
        "image": "1645621379-OFBMYAhKNDif.jpg",
        "created_at": "2022-02-23T13:02:59.000000Z",
        "updated_at": "2022-02-23T13:02:59.000000Z"
    }
    """
  Scenario: When I POST request data should be seen on database

    Then Following records should be seen at table "MAIN.news"
      | id | category_id | user_id | title | content | image                       | created_at          | updated_at          |
      |  2 |           2 |       1 | OF    | OF      | 1645621379-OFBMYAhKNDif.jpg | 2022-02-23 13:02:59 | 2022-02-23 13:02:59 |
