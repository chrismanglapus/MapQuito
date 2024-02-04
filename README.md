# MAPQUITO
MAPQUITO: A DENGUE VIRUS DYNAMIC HEATMAP WITH PRESCRIPTIVE ANALYTICS

## Statement of Objectives
Specifically, this study seeks to answer the following:
1. Develop a model to prescribe mitigating measures based from the number of cases and existing measures of the City Health Office.
2. Develop a dengue heatmap system that displays a visualization of dengue virus cases and integrating the model
3. Determine the impression of the user on the developed heatmap using the User Experience Questionnaire.
	a. Attractiveness
	b. Perspicuity
	c. Efficiency
	d. Dependability
	e. Stimulation
	f. Novelty

## Request Payload
1. Insert Data (postName):
    - Request Payload:
        {
            "lname": "hortizuela",
            "fname": "manny"
        }

2. Extract Data (printName):
    - No request payload is required for a GET request. You typically use the request URL to specify any parameters or filters for the extraction.

3. Update Data (updateName):
    - Request Payload:
        {
            "id": 1,
            "lname": "wick",
            "fname": "john"
        }

4. Delete Data (deleteName):
    - Request Payload:
        {
            "id": 1,
        }

## Response
1. Insert Data (postName):
    - Response Payload:
        {
            "status": "success",
            "data": null
        }

2. Extract Data (printName):
    - Response Payload:
        {
            "status": "success",
            "data": [
                { "lname": "hortizuela", "fname": "manny" },
                // Add more name objects as retrieved from the database
            ]
        }

3. Update Data (updateName):
    - Response Payload:
        {
            "status": "success",
             "data": null
        }

4. Delete Data (deleteName):
    - Response Payload:
        {
            "status": "success",
            "data": null
        }

## Usage
1. Insert Data (postName):
    - Insert in Body:
    {
        "lname": "yourlastname",
        "fname": "yourfirstname"
    }

2. Extract Data (printName):
    - Go to:
        127.0.0.1/api/public/printName

3. Update Data (updateName):
    - Insert in Body:
        {
            "id": 1,
            "lname": "yourlnameupdated",
            "fname": "yourfnameupdated"
        }
    - NOTE: Replace the ID number matching what ID you want to replace, lname and fname to whatever you want.

3. Delete Data (deleteName):
    - Insert in Body:
        {
            "id":2
        }
    - NOTE: Change ID to whatever ID you want to delete.

## License
This API is released under the ___ Licence
- You are free to use this API for both personal and commercial projects.
- You may modify, distribute, and sublicense this API as long as you follow the terms of the XYZ License.
- There is no warranty or liability. Use this API at your own risk.

## Contributors

## Contact Information
If you have any questions, need support, or want to provide feedback about this API, you can contact us through the following channels:
- **Email**: [christianmanglapus03@gmai.com](mailto:christianmanglapus03@gmail.com)
- **GitHub Issues**: [Create a new issue](https://github.com/chrismanglapus/github-setup/issues) on our GitHub repository.
