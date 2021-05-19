## Spreadsheet Evaluator

### Task
Implement an application that is able to evaluate data structure representing a spreadsheet.

### Used:
- API GET and POST
- OOP
- Static classes
- RegEx
- Recursion
- JSON

### HUB API
Application connects to the hub, downloads a batch of jobs, computes them and submites
them back.
Hub URL below, all paths are relative to it. **Some URLs are dynamic
and can only be retrieved through the API**.

Hub URL: https://www.wix.com/_serverless/hiring-task-spreadsheet-evaluator


----------------------------------
- ### GET / jobs
**HTTP 200**
```
{
  "submissionUrl": "...",
  "jobs": [
      { "id": "j1", "data": ... },
      { "id": "j2", "data": ... }
  ]
}
```
- ### POST `<submissionUrl>`
```
{
  "email": "my-email@gmail.com",
  "results": [
      { "id": "j1", "data": ... },
      { "id": "j2", "data": ... }
  ]
}
```
**HTTP 200**
```
{
"message": "Good job!"
}
```
----------------------------------
### Spreadsheet Data Structure (aka "The Job")

Application's purpose is to evaluate a spreadsheet and return a fully evaluated spreadsheet
back. A spreadsheet is represented as a 2-dimensional array of cells. Cells can be referred to
using A1 notation.

#### Spreadsheet
```
[
  [
    { … }, // Cell A1
    { … }, // Cell B1
  ],
  [
    { … }, // Cell A2
    { … }, // Cell B2
  ],
]
```

### Value Cells

Value cells represent constant values. They can be one of 3 subtypes defined in the table.

Subtype      |Example       | 
------------ | -------------|
Number value | `{ "value": { "number": 45.8 } }`|
Text value   | `{ "value": { "text": "hello world!" } }` |
Boolean value| `{ "value": { "boolean": true } }` |

Typing is strict and no implicit conversions between the types is possible. This is mostly to
simplify the implementation.


### Error Cells

Error cells represent an error that occurred during evaluation. In essence, it's just an error
message describing what went wrong.

Type         |Example       | 
------------ | -------------|
Erros        | `{ "error": "invalid reference" }`


### Formula Cells
These are the complicated ones. Formula is a composite data structure that can (in most cases)
be evaluated to a constant value. It means that after evaluation, the formula cell is replaced by
either a value cell or error cell. Formula structure is very strict (to simplify the implementation). In
case input structure is invalid, types do not match or refer to non-existing cells - result is an
error. Error messages are not defined in this document and therefore not checked by the hub.

Formula cells always have the following structure. Property value is one of the
operators defined below.

Type:        |Formula       | 
------------ | -------------|
#### Example
- `{ "formula": { "value": { "number": 42 } } }`
- `{ "formula": { "reference": "A2" } }`
- 
```
{
  "formula": {
    "sum": [
        { "value": { "number": 2 } },
        { "reference": "B1" }
     ]
   }
 }
 ```

### Leaf Nodes

Operator     | Comment      | Example      |
------------ | -------------| -------------|
value | Evaluates to a constant value. Structure is of a value cell. | `{ "value": { "number": 42 } }` |
reference | Resolves to a cell value referred to using A1 notation. | `{ "reference": "C5" }` |


### Tree Nodes (operators)
sum | multiply | divide | is_greater | is_equal | not | and | or | if | concat | 
----| ---------| -------| -----------| ---------| ----| ----| ---| ---| -------|


----------------------------------

### A1 Notation

This is a more strict (than usual) variation of A1 notation in order to simplify the implementation.
This notation allows referencing a single cell in the spreadsheet. It will always match a regular
expression `[A-Z][0-9]+`.

Letter represents column - numbered A to Z. Number afterwards represents row number,
numbered 1 and upwards.


### Example

**Simple**

***Job Received***
```
{
  "submissionUrl": "https://wix.com/_something/submit",
  "jobs": [
      {
        "id": "j123",
        "data": [
           [
              { "value": { "number": 6 } }, // Cell A1 ( =6 )
              { "value": { "number": 4 } }, // Cell B1 ( =4 )
              {                             // Cell C1 ( =A1 + B1 )
                "formula": {
                    "sum": [
                        { "reference": "A1" },
                        { "reference": "B1" }
                   ]
                }
              }
            ]
         ]
      }
   ]
}
```
***Results submitted***
```
{
  "email": "my-email@gmail.com",
  "results": [
    {
      "id": "j123",
      "data": [
        [
          { "value": { "number": 6 } }, // Cell A1 ( =6 )
          { "value": { "number": 4 } }, // Cell B1 ( =4 )
          { "value": { "number": 10 } }, // Cell C1 ( =10 )
        ]
      ]
    }
  ]
}
```

----------------------------------
## Result
Program work well


## Example
- ### **GET**
```
{
    "submissionUrl": "https://www.wix.com/_serverless/hiring-task-spreadsheet-evaluator/submit/eyJ0YWdzIjpbXX0",
    "jobs": [
        {
            "id": "job-0",
            "data": []
        },
        {
            "id": "job-1",
            "data": [
                [
                    {
                        "value": {
                            "number": 5
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-2",
            "data": [
                [
                    {
                        "value": {
                            "number": 5
                        }
                    },
                    {
                        "formula": {
                            "reference": "A1"
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-3",
            "data": [
                [
                    {
                        "value": {
                            "number": 6
                        }
                    },
                    {
                        "value": {
                            "number": 4
                        }
                    },
                    {
                        "formula": {
                            "sum": [
                                {
                                    "reference": "A1"
                                },
                                {
                                    "reference": "B1"
                                }
                            ]
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-4",
            "data": [
                [
                    {
                        "value": {
                            "number": 6
                        }
                    },
                    {
                        "value": {
                            "number": 4
                        }
                    },
                    {
                        "value": {
                            "number": 7
                        }
                    },
                    {
                        "formula": {
                            "sum": [
                                {
                                    "reference": "A1"
                                },
                                {
                                    "reference": "B1"
                                },
                                {
                                    "reference": "C1"
                                }
                            ]
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-5",
            "data": [
                [
                    {
                        "value": {
                            "number": 6
                        }
                    },
                    {
                        "value": {
                            "number": 4
                        }
                    },
                    {
                        "formula": {
                            "multiply": [
                                {
                                    "reference": "A1"
                                },
                                {
                                    "reference": "B1"
                                }
                            ]
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-6",
            "data": [
                [
                    {
                        "value": {
                            "number": 6
                        }
                    },
                    {
                        "value": {
                            "number": 4
                        }
                    },
                    {
                        "value": {
                            "number": -1
                        }
                    },
                    {
                        "formula": {
                            "multiply": [
                                {
                                    "reference": "A1"
                                },
                                {
                                    "reference": "B1"
                                },
                                {
                                    "reference": "C1"
                                }
                            ]
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-7",
            "data": [
                [
                    {
                        "value": {
                            "number": 6
                        }
                    },
                    {
                        "value": {
                            "number": 4
                        }
                    },
                    {
                        "formula": {
                            "divide": [
                                {
                                    "reference": "A1"
                                },
                                {
                                    "reference": "B1"
                                }
                            ]
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-8",
            "data": [
                [
                    {
                        "value": {
                            "number": 1
                        }
                    },
                    {
                        "value": {
                            "number": 3
                        }
                    },
                    {
                        "formula": {
                            "divide": [
                                {
                                    "reference": "A1"
                                },
                                {
                                    "reference": "B1"
                                }
                            ]
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-9",
            "data": [
                [
                    {
                        "value": {
                            "number": 1
                        }
                    },
                    {
                        "value": {
                            "number": 3
                        }
                    },
                    {
                        "formula": {
                            "is_greater": [
                                {
                                    "reference": "A1"
                                },
                                {
                                    "reference": "B1"
                                }
                            ]
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-10",
            "data": [
                [
                    {
                        "value": {
                            "number": 1.2
                        }
                    },
                    {
                        "value": {
                            "number": 1.2
                        }
                    },
                    {
                        "formula": {
                            "is_equal": [
                                {
                                    "reference": "A1"
                                },
                                {
                                    "reference": "B1"
                                }
                            ]
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-11",
            "data": [
                [
                    {
                        "value": {
                            "boolean": false
                        }
                    },
                    {
                        "formula": {
                            "not": {
                                "reference": "A1"
                            }
                        }
                    }
                ],
                [
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "formula": {
                            "not": {
                                "reference": "A2"
                            }
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-12",
            "data": [
                [
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "value": {
                            "boolean": false
                        }
                    },
                    {
                        "formula": {
                            "and": [
                                {
                                    "reference": "A1"
                                },
                                {
                                    "reference": "B1"
                                }
                            ]
                        }
                    }
                ],
                [
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "formula": {
                            "and": [
                                {
                                    "reference": "A2"
                                },
                                {
                                    "reference": "B2"
                                }
                            ]
                        }
                    }
                ],
                [
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "value": {
                            "number": 1
                        }
                    },
                    {
                        "formula": {
                            "and": [
                                {
                                    "reference": "A3"
                                },
                                {
                                    "reference": "B3"
                                }
                            ]
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-13",
            "data": [
                [
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "value": {
                            "boolean": false
                        }
                    },
                    {
                        "formula": {
                            "and": [
                                {
                                    "reference": "A1"
                                },
                                {
                                    "reference": "B1"
                                },
                                {
                                    "reference": "C1"
                                }
                            ]
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-14",
            "data": [
                [
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "value": {
                            "boolean": false
                        }
                    },
                    {
                        "formula": {
                            "or": [
                                {
                                    "reference": "A1"
                                },
                                {
                                    "reference": "B1"
                                }
                            ]
                        }
                    }
                ],
                [
                    {
                        "value": {
                            "boolean": false
                        }
                    },
                    {
                        "value": {
                            "boolean": false
                        }
                    },
                    {
                        "formula": {
                            "or": [
                                {
                                    "reference": "A2"
                                },
                                {
                                    "reference": "B2"
                                }
                            ]
                        }
                    }
                ],
                [
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "value": {
                            "number": 1
                        }
                    },
                    {
                        "formula": {
                            "or": [
                                {
                                    "reference": "A3"
                                },
                                {
                                    "reference": "B3"
                                }
                            ]
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-15",
            "data": [
                [
                    {
                        "value": {
                            "boolean": false
                        }
                    },
                    {
                        "value": {
                            "boolean": false
                        }
                    },
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "formula": {
                            "or": [
                                {
                                    "reference": "A1"
                                },
                                {
                                    "reference": "B1"
                                },
                                {
                                    "reference": "C1"
                                }
                            ]
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-16",
            "data": [
                [
                    {
                        "value": {
                            "number": 2
                        }
                    },
                    {
                        "value": {
                            "number": 1.5
                        }
                    },
                    {
                        "formula": {
                            "if": [
                                {
                                    "is_greater": [
                                        {
                                            "reference": "A1"
                                        },
                                        {
                                            "reference": "B1"
                                        }
                                    ]
                                },
                                {
                                    "reference": "A1"
                                },
                                {
                                    "reference": "B1"
                                }
                            ]
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-17",
            "data": [
                [
                    {
                        "formula": {
                            "concat": [
                                {
                                    "value": {
                                        "text": "Hello"
                                    }
                                },
                                {
                                    "value": {
                                        "text": ", "
                                    }
                                },
                                {
                                    "value": {
                                        "text": "World!"
                                    }
                                }
                            ]
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-18",
            "data": [
                [
                    {
                        "value": {
                            "text": "First"
                        }
                    },
                    {
                        "formula": {
                            "reference": "A1"
                        }
                    },
                    {
                        "formula": {
                            "reference": "B1"
                        }
                    },
                    {
                        "formula": {
                            "reference": "C1"
                        }
                    },
                    {
                        "formula": {
                            "reference": "D1"
                        }
                    },
                    {
                        "formula": {
                            "reference": "E1"
                        }
                    },
                    {
                        "formula": {
                            "reference": "F1"
                        }
                    },
                    {
                        "formula": {
                            "reference": "G1"
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-19",
            "data": [
                [
                    {
                        "value": {
                            "text": "First"
                        }
                    }
                ],
                [
                    {
                        "formula": {
                            "reference": "A1"
                        }
                    }
                ],
                [
                    {
                        "formula": {
                            "reference": "A2"
                        }
                    }
                ],
                [
                    {
                        "formula": {
                            "reference": "A3"
                        }
                    }
                ],
                [
                    {
                        "formula": {
                            "reference": "A4"
                        }
                    }
                ],
                [
                    {
                        "formula": {
                            "reference": "A5"
                        }
                    }
                ],
                [
                    {
                        "formula": {
                            "reference": "A6"
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-20",
            "data": [
                [
                    {
                        "formula": {
                            "reference": "B1"
                        }
                    },
                    {
                        "formula": {
                            "reference": "C1"
                        }
                    },
                    {
                        "formula": {
                            "reference": "D1"
                        }
                    },
                    {
                        "formula": {
                            "reference": "E1"
                        }
                    },
                    {
                        "formula": {
                            "reference": "F1"
                        }
                    },
                    {
                        "formula": {
                            "reference": "G1"
                        }
                    },
                    {
                        "formula": {
                            "reference": "H1"
                        }
                    },
                    {
                        "value": {
                            "text": "Last"
                        }
                    }
                ]
            ]
        }
    ]
}
```


- ### **POST**

```
{
    "email": "my-email@gmail.com",
    "results": [
        {
            "id": "job-0",
            "data": []
        },
        {
            "id": "job-1",
            "data": [
                [
                    {
                        "value": {
                            "number": 5
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-2",
            "data": [
                [
                    {
                        "value": {
                            "number": 5
                        }
                    },
                    {
                        "value": {
                            "number": 5
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-3",
            "data": [
                [
                    {
                        "value": {
                            "number": 6
                        }
                    },
                    {
                        "value": {
                            "number": 4
                        }
                    },
                    {
                        "value": {
                            "number": 10
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-4",
            "data": [
                [
                    {
                        "value": {
                            "number": 6
                        }
                    },
                    {
                        "value": {
                            "number": 4
                        }
                    },
                    {
                        "value": {
                            "number": 7
                        }
                    },
                    {
                        "value": {
                            "number": 17
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-5",
            "data": [
                [
                    {
                        "value": {
                            "number": 6
                        }
                    },
                    {
                        "value": {
                            "number": 4
                        }
                    },
                    {
                        "value": {
                            "number": 24
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-6",
            "data": [
                [
                    {
                        "value": {
                            "number": 6
                        }
                    },
                    {
                        "value": {
                            "number": 4
                        }
                    },
                    {
                        "value": {
                            "number": -1
                        }
                    },
                    {
                        "value": {
                            "number": -24
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-7",
            "data": [
                [
                    {
                        "value": {
                            "number": 6
                        }
                    },
                    {
                        "value": {
                            "number": 4
                        }
                    },
                    {
                        "value": {
                            "number": 1.5
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-8",
            "data": [
                [
                    {
                        "value": {
                            "number": 1
                        }
                    },
                    {
                        "value": {
                            "number": 3
                        }
                    },
                    {
                        "value": {
                            "number": 0.333333333333
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-9",
            "data": [
                [
                    {
                        "value": {
                            "number": 1
                        }
                    },
                    {
                        "value": {
                            "number": 3
                        }
                    },
                    {
                        "value": {
                            "boolean": false
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-10",
            "data": [
                [
                    {
                        "value": {
                            "number": 1.2
                        }
                    },
                    {
                        "value": {
                            "number": 1.2
                        }
                    },
                    {
                        
                        "value": {
                            "boolean": true
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-11",
            "data": [
                [
                    {
                        "value": {
                            "boolean": false
                        }
                    },
                    {
                        
                        "value": {
                            "boolean": true
                        }
                    }
                ],
                [
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        
                        "value": {
                            "boolean": false
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-12",
            "data": [
                [
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "value": {
                            "boolean": false
                        }
                    },
                    {
                        
                        "value": {
                            "boolean": false
                        }
                    }
                ],
                [
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        
                        "value": {
                            "boolean": true
                        }
                    }
                ],
                [
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "value": {
                            "number": 1
                        }
                    },
                    {
                        
                        "error": "type does not match"
                    }
                ]
            ]
        },
        {
            "id": "job-13",
            "data": [
                [
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "value": {
                            "boolean": false
                        }
                    },
                    {
                        "value": {
                            "boolean": false
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-14",
            "data": [
                [
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "value": {
                            "boolean": false
                        }
                    },
                    {
                        "value": {
                            "boolean": true
                        }
                    }
                ],
                [
                    {
                        "value": {
                            "boolean": false
                        }
                    },
                    {
                        "value": {
                            "boolean": false
                        }
                    },
                    {
                        "value": {
                            "boolean": false
                        }
                    }
                ],
                [
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "value": {
                            "number": 1
                        }
                    },
                    {
                        "error": "type does not match"
                    }
                ]
            ]
        },
        {
            "id": "job-15",
            "data": [
                [
                    {
                        "value": {
                            "boolean": false
                        }
                    },
                    {
                        "value": {
                            "boolean": false
                        }
                    },
                    {
                        "value": {
                            "boolean": true
                        }
                    },
                    {
                        "value": {
                            "boolean": true
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-16",
            "data": [
                [
                    {
                        "value": {
                            "number": 2
                        }
                    },
                    {
                        "value": {
                            "number": 1.5
                        }
                    },
                    {
                        "value": {
                            "number": 2
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-17",
            "data": [
                [
                    {
                        "value": {
                                        "text": "Hello, World!"
                                    }
                    }
                ]
            ]
        },
        {
            "id": "job-18",
            "data": [
                [
                    {
                        "value": {
                            "text": "First"
                        }
                    },
                    {
                        "value": {
                            "text": "First"
                        }
                    },
                    {
                        "value": {
                            "text": "First"
                        }
                    },
                    {
                        "value": {
                            "text": "First"
                        }
                    },
                    {
                        "value": {
                            "text": "First"
                        }
                    },
                    {
                        "value": {
                            "text": "First"
                        }
                    },
                    {
                        "value": {
                            "text": "First"
                        }
                    },
                    {
                        "value": {
                            "text": "First"
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-19",
            "data": [
                [
                    {
                        "value": {
                            "text": "First"
                        }
                    }
                ],
                [
                    {
                         "value": {
                            "text": "First"
                        }
                    }
                ],
                [
                    {
                         "value": {
                            "text": "First"
                        }
                    }
                ],
                [
                    {
                         "value": {
                            "text": "First"
                        }
                    }
                ],
                [
                    {
                        "value": {
                            "text": "First"
                        }
                    }
                ],
                [
                    {
                         "value": {
                            "text": "First"
                        }
                    }
                ],
                [
                    {
                         "value": {
                            "text": "First"
                        }
                    }
                ]
            ]
        },
        {
            "id": "job-20",
            "data": [
                [
                    {
                        "value": {
                            "text": "Last"
                        }
                    },
                    {
                        "value": {
                            "text": "Last"
                        }
                    },
                    {
                        "value": {
                            "text": "Last"
                        }
                    },
                    {
                        "value": {
                            "text": "t"
                        }
                    },
                    {
                        "value": {
                            "text": "Last"
                        }
                    },
                    {
                        "value": {
                            "text": "Last"
                        }
                    },
                    {
                        "value": {
                            "text": "Last"
                        }
                    },
                    {
                        "value": {
                            "text": "Last"
                        }
                    }
                ]
            ]
        }
    ]
}
```
